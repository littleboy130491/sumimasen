<?php

namespace Littleboy130491\Sumimasen\Http\Controllers;

use Artesaos\SEOTools\Facades\SEOTools;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Littleboy130491\SeoSuite\Traits\SetsSeoSuite;
use Littleboy130491\Sumimasen\Enums\ContentStatus;
use Littleboy130491\Sumimasen\Models\Page;

class ContentController extends Controller
{
    use SetsSeoSuite;

    protected string $templateBase = 'templates';
    protected string $packageNamespace = 'sumimasen-cms';
    protected string $defaultLanguage;
    protected int $paginationLimit;
    protected string $staticPageClass;
    protected string $frontPageSlug;

    public function __construct()
    {
        $this->defaultLanguage = Config::get('cms.default_language');
        $this->paginationLimit = Config::get('cms.pagination_limit', 12);
        $this->staticPageClass = Config::get('cms.static_page_model', Page::class);
        $this->frontPageSlug = Config::get('cms.front_page_slug', 'home');
        $this->templateBase = Config::get('cms.template_base', 'templates');
        $this->packageNamespace = Config::get('cms.package_namespace', 'sumimasen-cms');
    }

    /**
     * Display the home page content - finds home page by slug or first available page
     */
    public function home(string $lang)
    {
        $modelClass = $this->getValidModelClass($this->staticPageClass);

        // Find home page by configured slug
        $content = $modelClass::where('status', ContentStatus::Published)
            ->whereJsonContainsLocale('slug', $this->defaultLanguage, $this->frontPageSlug)
            ->first();

        // Fallback to first available page if home not found
        if (!$content) {
            $content = $modelClass::where('status', ContentStatus::Published)
                ->orderBy('id', 'asc')
                ->first();

            if (!$content) {
                abort(404, 'Home page content not found.');
            }
        }

        if (method_exists($this, 'setsSeo')) {
            $this->setsSeo($content);
        }

        return $this->renderContentView(
            template: $this->resolveHomeTemplate($content),
            lang: $lang,
            content: $content,
            viewData: [
                'content' => $content,
            ]
        );
    }

    /**
     * Display a static page by slug with front page redirect and fallback handling
     */
    public function staticPage(Request $request, string $lang, string $page_slug)
    {
        // Redirect to home if this is the front page
        if ($this->isFrontPage($lang, $page_slug)) {
            return $this->redirectToHome($lang, $request);
        }

        $modelClass = $this->getValidModelClass($this->staticPageClass);
        $content = $this->findContent($modelClass, $lang, $page_slug);

        // Handle localized slug redirects
        if ($content) {
            if ($redirect = $this->maybeRedirectToLocalizedSlug('cms.static.page', $lang, $page_slug, $content, 'page_slug')) {
                return $redirect;
            }
        }

        // Try fallback content model if page not found
        if (!$content) {
            $fallbackResult = $this->tryFallbackContentModel($lang, $page_slug, $request);
            if ($fallbackResult) {
                return $fallbackResult;
            }
            abort(404, "Page not found for slug '{$page_slug}'");
        }

        if (method_exists($this, 'setsSeo')) {
            $this->setsSeo($content);
        }

        return $this->renderContentView(
            template: $this->resolvePageTemplate($content),
            lang: $lang,
            content: $content,
            viewData: [
                'content' => $content,
            ]
        );
    }

    /**
     * Display a single content item by content type and slug
     */
    public function singleContent(Request $request, string $lang, string $content_type_key, string $content_slug)
    {
        // Redirect to static page route if this is a static page
        if ($content_type_key === Config::get('cms.static_page_slug')) {
            return redirect()->route('cms.static.page', array_merge(
                ['lang' => $lang, 'page_slug' => $content_slug],
                $request->query()
            ));
        }

        $modelClass = $this->getContentModelClass($content_type_key);
        $content = $this->findContent($modelClass, $lang, $content_slug);

        // Handle localized slug redirects
        if ($content) {
            if ($redirect = $this->maybeRedirectToLocalizedSlug('cms.single.content', $lang, $content_slug, $content, 'content_slug')) {
                return $redirect;
            }
        }

        if (!$content) {
            throw (new ModelNotFoundException)->setModel(
                $modelClass,
                "No content found for slug '{$content_slug}'"
            );
        }

        $this->incrementPageViewsIfSupported($content);

        if (method_exists($this, 'setsSeo')) {
            $this->setsSeo($content);
        }

        return $this->renderContentView(
            template: $this->resolveSingleTemplate($content, $content_type_key, $content_slug),
            lang: $lang,
            content: $content,
            contentTypeKey: $content_type_key,
            contentSlug: $content_slug,
            viewData: [
                'content_type' => $content_type_key,
                'content_slug' => $content_slug,
                'content' => $content,
                'title' => $content->title,
            ]
        );
    }

    /**
     * Display an archive page listing all content of a specific type
     */
    public function archiveContent(string $lang, string $content_type_archive_key)
    {
        $modelClass = $this->getContentModelClass($content_type_archive_key);
        $originalKey = $this->getOriginalContentTypeKey($content_type_archive_key);
        $eagerLoadRelationships = $this->getEagerLoadRelationships($originalKey);

        $posts = $modelClass::with($eagerLoadRelationships)
            ->where('status', ContentStatus::Published)
            ->orderBy('created_at', 'desc')
            ->paginate($this->paginationLimit);

        $archive = $this->createArchiveObject($content_type_archive_key);
        $this->setArchiveSeoMetadata($content_type_archive_key);

        return $this->renderContentView(
            template: $this->resolveArchiveTemplate($content_type_archive_key),
            lang: $lang,
            content: $archive,
            contentTypeKey: $content_type_archive_key,
            viewData: [
                'post_type' => $content_type_archive_key,
                'archive' => $archive,
                'title' => 'Archive: ' . Str::title(str_replace('-', ' ', $content_type_archive_key)),
                'posts' => $posts,
            ]
        );
    }

    /**
     * Display a taxonomy archive page showing all content related to a specific taxonomy term
     */
    public function taxonomyArchive(string $lang, string $taxonomy_key, string $taxonomy_slug)
    {
        $modelClass = $this->getContentModelClass($taxonomy_key);
        $taxonomyModel = $this->findContent($modelClass, $lang, $taxonomy_slug);

        // Handle localized slug redirects
        if ($taxonomyModel) {
            if ($redirect = $this->maybeRedirectToLocalizedSlug('cms.taxonomy.archive', $lang, $taxonomy_slug, $taxonomyModel, 'taxonomy_slug')) {
                return $redirect;
            }
        }

        if (!$taxonomyModel) {
            throw (new ModelNotFoundException)->setModel(
                $modelClass,
                "Taxonomy not found for slug '{$taxonomy_slug}'"
            );
        }

        $posts = $this->getTaxonomyRelatedContent($taxonomyModel, $taxonomy_key);

        if (method_exists($this, 'setsSeo')) {
            $this->setsSeo($taxonomyModel);
        }

        return $this->renderContentView(
            template: $this->resolveTaxonomyTemplate($taxonomy_slug, $taxonomy_key, $taxonomyModel),
            lang: $lang,
            content: $taxonomyModel,
            contentTypeKey: $taxonomy_key,
            contentSlug: $taxonomy_slug,
            viewData: [
                'taxonomy' => $taxonomy_key,
                'taxonomy_slug' => $taxonomy_slug,
                'taxonomy_model' => $taxonomyModel,
                'title' => $taxonomyModel->title ??
                    Str::title(str_replace('-', ' ', $taxonomy_key)) . ': ' .
                    Str::title(str_replace('-', ' ', $taxonomy_slug)),
                'posts' => $posts,
            ]
        );
    }

    // ===== CACHED LOOKUP METHODS =====

    /**
     * Get cached slug-to-key mapping for performance optimization
     */
    private function getSlugToKeyMap(): array
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
    private function getKeyToConfigMap(): array
    {
        return cache()->remember('cms.key_to_config_map', 86400, function () {
            return Config::get('cms.content_models', []);
        });
    }

    /**
     * Get the original config key from a slug (handles custom slug overrides)
     */
    private function getOriginalContentTypeKey(string $slug): string
    {
        $slugToKeyMap = $this->getSlugToKeyMap();
        return $slugToKeyMap[$slug] ?? $slug;
    }

    /**
     * Get the model class for a content type key (handles slug overrides)
     */
    private function getContentModelClass(string $contentTypeKey): string
    {
        $slugToKeyMap = $this->getSlugToKeyMap();
        $keyToConfigMap = $this->getKeyToConfigMap();

        $modelKey = $slugToKeyMap[$contentTypeKey] ?? null;

        if (!$modelKey) {
            abort(404, "Content type '{$contentTypeKey}' not found in configuration.");
        }

        $modelConfig = $keyToConfigMap[$modelKey];
        $modelClass = $modelConfig['model'];

        if (!class_exists($modelClass)) {
            abort(404, "Model for content type '{$contentTypeKey}' not found or not configured correctly.");
        }

        return $modelClass;
    }

    // ===== CONTENT FINDING METHODS =====

    /**
     * Find content by slug in requested language with fallback to default language
     */
    private function findContent(string $modelClass, string $requestedLocale, string $slug): ?Model
    {
        $defaultLanguage = $this->defaultLanguage;

        // Try the requested locale first
        $content = $modelClass::where('status', ContentStatus::Published)
            ->whereJsonContainsLocale('slug', $requestedLocale, $slug)
            ->first();

        // Fallback to default locale if not found
        if (!$content && $requestedLocale !== $defaultLanguage) {
            $content = $modelClass::where('status', ContentStatus::Published)
                ->whereJsonContainsLocale('slug', $defaultLanguage, $slug)
                ->first();
        }

        return $content;
    }

    /**
     * Try to find content in fallback content model and redirect if found
     */
    private function tryFallbackContentModel(string $lang, string $slug, Request $request): ?\Illuminate\Http\RedirectResponse
    {
        $fallbackContentType = Config::get('cms.fallback_content_type', 'posts');
        $modelClass = Config::get("cms.content_models.{$fallbackContentType}.model");

        if (!$modelClass || !class_exists($modelClass)) {
            return null;
        }

        $content = $this->findContent($modelClass, $lang, $slug);

        if ($content) {
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
     * Render a view with common data and body classes
     */
    private function renderContentView(
        string $template,
        string $lang,
        $content = null,
        ?string $contentTypeKey = null,
        ?string $contentSlug = null,
        array $viewData = []
    ) {
        $bodyClasses = $this->generateBodyClasses($lang, $content, $contentTypeKey, $contentSlug);

        $defaultData = [
            'lang' => $lang,
            'bodyClasses' => $bodyClasses,
        ];

        return view($template, array_merge($defaultData, $viewData));
    }

    /**
     * Validate and return a model class, fallback to Page if invalid
     */
    private function getValidModelClass(string $modelClass): string
    {
        if (!$modelClass || !class_exists($modelClass)) {
            return Page::class;
        }

        return $modelClass;
    }

    /**
     * Increment page views if the model supports it
     */
    private function incrementPageViewsIfSupported(Model $content): void
    {
        if (in_array(\Littleboy130491\Sumimasen\Traits\HasPageViews::class, class_uses_recursive($content))) {
            $content->incrementPageViews();
        }
    }

    /**
     * Create an archive object for archive pages
     */
    private function createArchiveObject(string $contentTypeKey): object
    {
        return (object) [
            'name' => Str::title(str_replace('-', ' ', $contentTypeKey)),
            'post_type' => $contentTypeKey,
            'description' => 'Archive of all ' . Str::title(str_replace('-', ' ', $contentTypeKey)) . ' content.',
        ];
    }

    /**
     * Set SEO metadata for archive pages using original config key
     */
    private function setArchiveSeoMetadata(string $contentTypeKey): void
    {
        $originalKey = $this->getOriginalContentTypeKey($contentTypeKey);
        $archiveTitle = Config::get("cms.content_models.{$originalKey}.archive_SEO_title");
        $archiveDescription = Config::get("cms.content_models.{$originalKey}.archive_SEO_description");

        if ($archiveTitle) {
            SEOTools::setTitle($archiveTitle);
        } else {
            SEOTools::setTitle('Archive: ' . Str::title(str_replace('-', ' ', $contentTypeKey)));
        }

        if ($archiveDescription) {
            SEOTools::setDescription($archiveDescription);
        } else {
            SEOTools::setDescription('Archive of all ' . $contentTypeKey);
        }
    }

    /**
     * Get content related to a taxonomy term with pagination
     */
    private function getTaxonomyRelatedContent(Model $taxonomyModel, string $taxonomyKey)
    {
        $originalKey = $this->getOriginalContentTypeKey($taxonomyKey);
        $relationshipName = Config::get("cms.content_models.{$originalKey}.display_content_from", 'posts');

        if (!method_exists($taxonomyModel, $relationshipName)) {
            \Illuminate\Support\Facades\Log::warning("Configured relationship '{$relationshipName}' not found for taxonomy '{$taxonomyKey}'. Falling back to 'posts'.");
            $relationshipName = 'posts';
        }

        if (method_exists($taxonomyModel, $relationshipName)) {
            // Get eager load relationships for the related content type
            $relatedContentEagerLoad = $this->getEagerLoadRelationships($relationshipName);

            return $taxonomyModel->{$relationshipName}()
                ->with($relatedContentEagerLoad)
                ->where('status', ContentStatus::Published)
                ->orderBy('created_at', 'desc')
                ->paginate($this->paginationLimit);
        }

        \Illuminate\Support\Facades\Log::warning("Relationship method '{$relationshipName}' ultimately not found for taxonomy '{$taxonomyKey}'. Serving empty collection.");

        return collect();
    }

    /**
     * Check if a slug represents the site's front page in any language
     */
    private function isFrontPage(string $lang, string $slug): bool
    {
        $frontPageSlug = Config::get('cms.front_page_slug', 'home');

        // Quick check for default language front page
        if ($lang == $this->defaultLanguage && $slug === $frontPageSlug) {
            return true;
        }

        // Check if this slug corresponds to the front page in any language
        $modelClass = $this->getValidModelClass($this->staticPageClass);
        $content = $modelClass::where('status', ContentStatus::Published)
            ->whereJsonContainsLocale('slug', $lang, $slug)
            ->first();

        if ($content) {
            $defaultLangSlug = $content->getTranslation('slug', $this->defaultLanguage, false);
            if ($defaultLangSlug && $defaultLangSlug === $frontPageSlug) {
                return true;
            }
        }

        return false;
    }

    /**
     * Redirect to home page with query parameters preserved
     */
    private function redirectToHome(string $lang, Request $request)
    {
        return redirect()->route('cms.home', array_merge(['lang' => $lang], $request->query()));
    }

    // ===== TEMPLATE RESOLUTION METHODS =====

    /**
     * Resolve template for home page with fallback hierarchy
     */
    private function resolveHomeTemplate(?Model $content = null): string
    {
        $templates = [
            "{$this->templateBase}.singles.home",
            "{$this->templateBase}.singles.front-page",
            "{$this->templateBase}.home",
            "{$this->templateBase}.front-page",
            "{$this->templateBase}.singles.default",
            "{$this->templateBase}.default",
        ];

        if ($content) {
            $customTemplates = $this->getContentCustomTemplates($content);
            $templates = array_merge($customTemplates, $templates);
        }

        return $this->findFirstExistingTemplate($templates);
    }

    /**
     * Resolve template for static pages with slug-specific and custom templates
     */
    private function resolvePageTemplate(Model $content): string
    {
        $slug = $content->slug;
        $defaultSlug = method_exists($content, 'getTranslation') ?
            ($content->getTranslation('slug', $this->defaultLanguage) ?? $slug) : $slug;

        $templates = [
            "{$this->templateBase}.singles.{$defaultSlug}",
            "{$this->templateBase}.singles.page",
            "{$this->templateBase}.page",
            "{$this->templateBase}.singles.default",
            "{$this->templateBase}.default",
        ];

        $customTemplates = $this->getContentCustomTemplates($content);
        $templates = array_merge($customTemplates, $templates);

        return $this->findFirstExistingTemplate($templates);
    }

    /**
     * Resolve template for single content with support for custom slugs and fallbacks
     */
    private function resolveSingleTemplate(?Model $content, string $content_type_key, string $contentSlug): string
    {
        $postTypeFromSlug = Str::kebab(Str::singular($content_type_key));
        $originalContentTypeKey = $this->getOriginalContentTypeKey($content_type_key);
        $postTypeFromKey = Str::kebab(Str::singular($originalContentTypeKey));

        $defaultSlug = method_exists($content, 'getTranslation') ?
            ($content->getTranslation('slug', $this->defaultLanguage) ?? $contentSlug) : $contentSlug;

        $templates = [
            // Templates based on the custom slug
            "{$this->templateBase}.singles.{$postTypeFromSlug}-{$defaultSlug}",
            "{$this->templateBase}.singles.{$postTypeFromSlug}",
            "{$this->templateBase}.{$postTypeFromSlug}",
        ];

        // Add fallback templates for original key if different from slug
        if ($postTypeFromSlug !== $postTypeFromKey) {
            $fallbackTemplates = [
                "{$this->templateBase}.singles.{$postTypeFromKey}-{$defaultSlug}",
                "{$this->templateBase}.singles.{$postTypeFromKey}",
                "{$this->templateBase}.{$postTypeFromKey}",
            ];
            $templates = array_merge($templates, $fallbackTemplates);
        }

        // Add default templates
        $templates = array_merge($templates, [
            "{$this->templateBase}.singles.default",
            "{$this->templateBase}.default",
        ]);

        if ($content) {
            $customTemplates = $this->getContentCustomTemplates($content);
            $templates = array_merge($customTemplates, $templates);
        }

        return $this->findFirstExistingTemplate($templates);
    }

    /**
     * Resolve template for archive pages with config-based custom views
     */
    private function resolveArchiveTemplate(string $content_type_archive_key): string
    {
        $originalKey = $this->getOriginalContentTypeKey($content_type_archive_key);
        $configView = Config::get("cms.content_models.{$originalKey}.archive_view");

        if ($configView && View::exists($configView)) {
            return $configView;
        }

        $templates = [
            "{$this->templateBase}.archives.archive-{$content_type_archive_key}",
            "{$this->templateBase}.archive-{$content_type_archive_key}",
            "{$this->templateBase}.archives.archive",
            "{$this->templateBase}.archive",
        ];

        return $this->findFirstExistingTemplate($templates);
    }

    /**
     * Resolve template for taxonomy archives with custom template support
     */
    private function resolveTaxonomyTemplate(string $taxonomySlug, string $taxonomy_key = 'taxonomy', ?Model $taxonomyModel = null): string
    {
        // Check for config-based custom view
        $originalKey = $this->getOriginalContentTypeKey($taxonomy_key);
        $configView = Config::get("cms.content_models.{$originalKey}.archive_view");

        if ($configView && View::exists($configView)) {
            return $configView;
        }

        $templates = [
            "{$this->templateBase}.archives.{$taxonomy_key}-{$taxonomySlug}",
            "{$this->templateBase}.archives.{$taxonomy_key}",
            "{$this->templateBase}.{$taxonomy_key}-{$taxonomySlug}",
            "{$this->templateBase}.{$taxonomy_key}",
            "{$this->templateBase}.archives.archive",
            "{$this->templateBase}.archive",
        ];

        // Add custom templates from taxonomy model if available
        if ($taxonomyModel) {
            $customTemplates = $this->getTaxonomyCustomTemplates($taxonomyModel);
            $templates = array_merge($customTemplates, $templates);
        }

        return $this->findFirstExistingTemplate($templates);
    }

    /**
     * Get custom templates from taxonomy model (taxonomy templates use archives directory)
     */
    private function getTaxonomyCustomTemplates(Model $taxonomyModel): array
    {
        $templates = [];

        if (!empty($taxonomyModel->template)) {
            $templates[] = "{$this->templateBase}.archives.{$taxonomyModel->template}";
        }

        if (method_exists($taxonomyModel, 'getTranslation')) {
            $defaultSlug = $taxonomyModel->getTranslation('slug', $this->defaultLanguage);
            if (!empty($defaultSlug)) {
                $templates[] = "{$this->templateBase}.archives.{$defaultSlug}";
            }
        }

        return $templates;
    }

    /**
     * Get custom templates from content model (template field and slug-based)
     */
    private function getContentCustomTemplates(Model $content): array
    {
        $templates = [];

        if (!empty($content->template)) {
            $templates[] = "{$this->templateBase}.{$content->template}";
        }

        if (method_exists($content, 'getTranslation')) {
            $defaultSlug = $content->getTranslation('slug', $this->defaultLanguage);
            if (!empty($defaultSlug)) {
                $templates[] = "{$this->templateBase}.{$defaultSlug}";
            }
        }

        return $templates;
    }

    /**
     * Find the first existing template from a list of template names
     */
    private function findFirstExistingTemplate(array $templates): string
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
     * Generate CSS classes for the body element based on content type and context
     */
    private function generateBodyClasses(string $lang, $content = null, ?string $contentTypeKey = null, ?string $contentSlug = null): string
    {
        $classes = ["lang-{$lang}"];

        if ($content) {
            if ($content instanceof Model) {
                $classes[] = 'type-' . ($contentTypeKey ?? Str::kebab(Str::singular($content->getTable())));

                $slugForClass = '';
                if (method_exists($content, 'getTranslation')) {
                    $slugForClass = $content->slug ?? $contentSlug;
                } else {
                    $slugForClass = $contentSlug;
                }

                if ($slugForClass) {
                    $classes[] = 'slug-' . $slugForClass;
                }

                if (!empty($content->template)) {
                    $classes[] = 'template-' . Str::kebab($content->template);
                }
            } elseif (is_object($content)) {
                if (isset($content->post_type)) {
                    $classes[] = 'archive-' . $content->post_type;
                } elseif (isset($content->taxonomy)) {
                    $classes[] = 'taxonomy-' . $content->taxonomy;
                    if (isset($content->taxonomy_slug)) {
                        $classes[] = 'term-' . $content->taxonomy_slug;
                    }
                }
            }
        } else {
            if ($contentTypeKey) {
                $classes[] = 'page-' . $contentTypeKey;
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

    /**
     * Handle redirects for localized slugs when URL doesn't match current language
     */
    protected function maybeRedirectToLocalizedSlug(
        string $routeName,
        string $lang,
        string $requestedSlug,
        Model $content,
        string $slugParamName = 'page_slug'
    ) {
        $localizedSlug = $content->getTranslation('slug', $lang, false);

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
    private function getEagerLoadRelationships(string $content_type_key): array
    {
        $originalKey = $this->getOriginalContentTypeKey($content_type_key);
        $config = config("cms.content_models.{$originalKey}");

        return $config['eager_load'] ?? [];
    }
}