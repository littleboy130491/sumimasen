<?php

namespace Littleboy130491\Sumimasen\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Littleboy130491\SeoSuite\Traits\SetsSeoSuite;
use Littleboy130491\Sumimasen\Enums\ContentStatus;
use Littleboy130491\Sumimasen\Models\Page;

abstract class BaseContentController extends Controller
{
    use SetsSeoSuite;

    protected string $templateBase = 'templates';

    protected string $packageNamespace = 'sumimasen-cms';

    protected string $defaultLanguage;

    protected int $paginationLimit;

    protected string $staticPageClass;

    protected string $frontPageSlug;

    protected array $statusColumnCache = [];

    public function __construct()
    {
        $this->defaultLanguage = Config::get('cms.default_language');
        $this->paginationLimit = Config::get('cms.pagination_limit', 12);
        $this->staticPageClass = Config::get('cms.static_page_model', Page::class);
        $this->frontPageSlug = Config::get('cms.front_page_slug', 'home');
        $this->templateBase = Config::get('cms.template_base', 'templates');
        $this->packageNamespace = Config::get('cms.package_namespace', 'sumimasen-cms');
    }

    // ===== CACHED LOOKUP METHODS =====

    /**
     * Get cached slug-to-key mapping for performance optimization
     */
    protected function getSlugToKeyMap(): array
    {
        return cache()->remember('cms.slug_to_key_map', 86400, function () {
            $map = [];
            $allModelConfigs = Config::get('cms.content_models', []);

            foreach ($allModelConfigs as $key => $config) {
                $slug = $config['slug'] ?? $key;
                $map[$slug] = $key;
            }

            return $map;
        });
    }

    /**
     * Get cached key-to-config mapping for performance optimization
     */
    protected function getKeyToConfigMap(): array
    {
        return cache()->remember('cms.key_to_config_map', 86400, function () {
            return Config::get('cms.content_models', []);
        });
    }

    /**
     * Get the original config key from a slug (handles custom slug overrides)
     */
    protected function getOriginalContentTypeKey(string $slug): string
    {
        $slugToKeyMap = $this->getSlugToKeyMap();

        return $slugToKeyMap[$slug] ?? $slug;
    }

    /**
     * Get the model class for a content type key (handles slug overrides)
     */
    protected function getContentModelClass(string $contentTypeKey): string
    {
        $slugToKeyMap = $this->getSlugToKeyMap();
        $keyToConfigMap = $this->getKeyToConfigMap();

        $modelKey = $slugToKeyMap[$contentTypeKey] ?? null;

        if (! $modelKey) {
            abort(404, "Content type '{$contentTypeKey}' not found in configuration.");
        }

        $modelConfig = $keyToConfigMap[$modelKey];
        $modelClass = $modelConfig['model'];

        if (! class_exists($modelClass)) {
            abort(404, "Model for content type '{$contentTypeKey}' not found or not configured correctly.");
        }

        return $modelClass;
    }

    // ===== CONTENT FINDING METHODS =====

    /**
     * Find content by slug in requested language with fallback to default language
     */
    protected function findContent(string $modelClass, string $requestedLocale, string $slug, bool $isPreview = false): ?Model
    {
        $defaultLanguage = $this->defaultLanguage;

        // Try the requested locale first
        $content = $this->buildQueryWithStatusFilter($modelClass, $isPreview)
            ->whereJsonContainsLocale('slug', $requestedLocale, $slug)
            ->first();

        // Fallback to default locale if not found
        if (! $content && $requestedLocale !== $defaultLanguage) {
            $content = $this->buildQueryWithStatusFilter($modelClass, $isPreview)
                ->whereJsonContainsLocale('slug', $defaultLanguage, $slug)
                ->first();
        }

        return $content;
    }

    /**
     * Try to find content in fallback content model and redirect if found
     */
    protected function tryFallbackContentModel(string $lang, string $slug, Request $request, bool $isPreview = false): ?\Illuminate\Http\RedirectResponse
    {
        $fallbackContentType = Config::get('cms.fallback_content_type', 'posts');
        $modelClass = Config::get("cms.content_models.{$fallbackContentType}.model");

        if (! $modelClass || ! class_exists($modelClass)) {
            return null;
        }

        $item = $this->findContent($modelClass, $lang, $slug, $isPreview);

        if ($item) {
            return redirect()->route('cms.single.content', array_merge([
                'lang' => $lang,
                'content_type_key' => $fallbackContentType,
                'content_slug' => $slug,
            ], $request->query()));
        }

        return null;
    }

    // ===== HELPER METHODS =====

    /**
     * Check if a model class has a 'status' column (cached per model class)
     */
    protected function hasStatusColumn(string $modelClass): bool
    {
        if (! isset($this->statusColumnCache[$modelClass])) {
            $this->statusColumnCache[$modelClass] = \Schema::hasColumn((new $modelClass)->getTable(), 'status');
        }

        return $this->statusColumnCache[$modelClass];
    }

    /**
     * Build a query with status filtering if the model has a status column
     * Supports preview mode for authenticated users
     */
    protected function buildQueryWithStatusFilter(string $modelClass, bool $isPreview = false): \Illuminate\Database\Eloquent\Builder
    {
        $query = $modelClass::query();

        if ($this->hasStatusColumn($modelClass)) {
            // In preview mode, authenticated users can see all content statuses
            if ($isPreview && auth()->check()) {
                // No status filter - show all content regardless of status
                return $query;
            }

            // Normal mode - only show published content
            $query->where('status', ContentStatus::Published);
        }

        return $query;
    }

    /**
     * Render a view with common data and body classes
     */
    protected function renderContentView(
        string $template,
        string $lang,
        $item = null,
        ?string $contentTypeKey = null,
        ?string $contentSlug = null,
        array $viewData = [],
        bool $isPreview = false
    ) {
        $bodyClasses = $this->generateBodyClasses($lang, $item, $contentTypeKey, $contentSlug);

        // Share data globally so all components can access it
        View::share('lang', $lang);
        View::share('bodyClasses', $bodyClasses);
        View::share('contentTypeKey', $contentTypeKey);
        View::share('preview', $isPreview);

        if ($item) {
            View::share('globalItem', $item);
        }

        $defaultData = [
            'lang' => $lang,
            'bodyClasses' => $bodyClasses,
        ];

        return view($template, array_merge($defaultData, $viewData));
    }

    /**
     * Validate and return a model class, fallback to Page if invalid
     */
    protected function getValidModelClass(string $modelClass): string
    {
        if (! $modelClass || ! class_exists($modelClass)) {
            return Page::class;
        }

        return $modelClass;
    }

    /**
     * Increment page views if the model supports it
     */
    protected function incrementPageViewsIfSupported(Model $item): void
    {
        // Only increment for guests (unauthorized users)
        if (! auth()->check() && in_array(\Littleboy130491\Sumimasen\Traits\HasPageViews::class, class_uses_recursive($item))) {
            $item->incrementPageViews();
        }
    }

    /**
     * Check if a slug represents the site's front page in any language
     */
    protected function isFrontPage(string $lang, string $slug): bool
    {
        $frontPageSlug = Config::get('cms.front_page_slug', 'home');

        // Quick check for default language front page
        if ($lang == $this->defaultLanguage && $slug === $frontPageSlug) {
            return true;
        }

        // Check if this slug corresponds to the front page in any language
        $modelClass = $this->getValidModelClass($this->staticPageClass);

        $item = $this->buildQueryWithStatusFilter($modelClass)
            ->whereJsonContainsLocale('slug', $lang, $slug)
            ->first();

        if ($item) {
            $defaultLangSlug = $item->getTranslation('slug', $this->defaultLanguage, false);
            if ($defaultLangSlug && $defaultLangSlug === $frontPageSlug) {
                return true;
            }
        }

        return false;
    }

    /**
     * Redirect to home page with query parameters preserved
     */
    protected function redirectToHome(string $lang, Request $request)
    {
        return redirect()->route('cms.home', array_merge(['lang' => $lang], $request->query()));
    }

    /**
     * Handle redirects for localized slugs when URL doesn't match current language
     */
    protected function maybeRedirectToLocalizedSlug(
        string $routeName,
        string $lang,
        string $requestedSlug,
        Model $item,
        string $slugParamName = 'page_slug'
    ) {
        $localizedSlug = $item->getTranslation('slug', $lang, false);

        if ($localizedSlug && $localizedSlug !== $requestedSlug) {
            $params = [
                'lang' => $lang,
                $slugParamName => $localizedSlug,
            ] + request()->query();

            return redirect()->route($routeName, $params, 301);
        }

        return null;
    }

    /**
     * Get eager load relationships for a content type using original config key
     */
    protected function getEagerLoadRelationships(string $content_type_key): array
    {
        $originalKey = $this->getOriginalContentTypeKey($content_type_key);
        $config = config("cms.content_models.{$originalKey}");

        return $config['eager_load'] ?? [];
    }

    // ===== TEMPLATE RESOLUTION METHODS =====

    /**
     * Find the first existing template from a list of template names
     */
    protected function findFirstExistingTemplate(array $templates): string
    {
        // First pass: check application views
        foreach ($templates as $template) {
            if (View::exists($template)) {
                return $template;
            }
        }

        // Check application default
        $applicationDefault = "{$this->templateBase}.default";
        if (View::exists($applicationDefault)) {
            return $applicationDefault;
        }

        // Second pass: check package namespaced templates
        foreach ($templates as $template) {
            $namespacedTemplate = "{$this->packageNamespace}::{$template}";
            if (View::exists($namespacedTemplate)) {
                return $namespacedTemplate;
            }
        }

        // Final fallback - package default
        $packageDefault = "{$this->packageNamespace}::{$this->templateBase}.default";
        if (View::exists($packageDefault)) {
            return $packageDefault;
        }

        // If nothing exists, throw exception
        throw new \Exception("No template found for base: {$this->templateBase}");
    }

    /**
     * Get custom templates from content model (template field and slug-based)
     */
    protected function getContentCustomTemplates(Model $item): array
    {
        $templates = [];

        if (! empty($item->template)) {
            $templates[] = "{$this->templateBase}.{$item->template}";
        }

        if (method_exists($item, 'getTranslation')) {
            $defaultSlug = $item->getTranslation('slug', $this->defaultLanguage);
            if (! empty($defaultSlug)) {
                $templates[] = "{$this->templateBase}.{$defaultSlug}";
            }
        }

        return $templates;
    }

    /**
     * Get custom templates from taxonomy model (taxonomy templates use archives directory)
     */
    protected function getTaxonomyCustomTemplates(Model $taxonomyModel): array
    {
        $templates = [];

        if (! empty($taxonomyModel->template)) {
            $templates[] = "{$this->templateBase}.archives.{$taxonomyModel->template}";
        }

        if (method_exists($taxonomyModel, 'getTranslation')) {
            $defaultSlug = $taxonomyModel->getTranslation('slug', $this->defaultLanguage);
            if (! empty($defaultSlug)) {
                $templates[] = "{$this->templateBase}.archives.{$defaultSlug}";
            }
        }

        return $templates;
    }

    /**
     * Generate CSS classes for the body element based on content type and context
     */
    protected function generateBodyClasses(string $lang, $item = null, ?string $contentTypeKey = null, ?string $contentSlug = null): string
    {
        $classes = ["lang-{$lang}"];

        if ($item) {
            if ($item instanceof Model) {
                $classes[] = 'type-'.($contentTypeKey ?? Str::kebab(Str::singular($item->getTable())));

                $slugForClass = '';
                if (method_exists($item, 'getTranslation')) {
                    $slugForClass = $item->slug ?? $contentSlug;
                } else {
                    $slugForClass = $contentSlug;
                }

                if ($slugForClass) {
                    $classes[] = 'slug-'.$slugForClass;
                }

                if (! empty($item->template)) {
                    $classes[] = 'template-'.Str::kebab($item->template);
                }
            } elseif (is_object($item)) {
                if (isset($item->post_type)) {
                    $classes[] = 'archive-'.$item->post_type;
                } elseif (isset($item->taxonomy)) {
                    $classes[] = 'taxonomy-'.$item->taxonomy;
                    if (isset($item->taxonomy_slug)) {
                        $classes[] = 'term-'.$item->taxonomy_slug;
                    }
                }
            }
        } else {
            if ($contentTypeKey) {
                $classes[] = 'page-'.$contentTypeKey;
            }
        }

        // Add route-specific classes
        if (request()->routeIs('cms.home')) {
            $classes[] = 'home';
        }
        if (request()->routeIs('cms.archive.content')) {
            $classes[] = 'archive-page';
        }
        if (request()->routeIs('cms.taxonomy.archive')) {
            $classes[] = 'taxonomy-archive-page';
        }

        return implode(' ', array_unique($classes));
    }
}
