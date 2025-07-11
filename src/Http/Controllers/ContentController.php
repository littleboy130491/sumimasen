<?php

namespace Littleboy130491\Sumimasen\Http\Controllers;

use Artesaos\SEOTools\Facades\SEOTools;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ItemNotFoundException;
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
        $item = $modelClass::where('status', ContentStatus::Published)
            ->whereJsonContainsLocale('slug', $this->defaultLanguage, $this->frontPageSlug)
            ->first();

        // Fallback to first available page if home not found
        if (!$item) {
            $item = $modelClass::where('status', ContentStatus::Published)
                ->orderBy('id', 'asc')
                ->first();

            if (!$item) {
                abort(404, 'Home page content not found.');
            }
        }

        if (method_exists($this, 'setsSeo')) {
            $this->setsSeo($item);
        }

        return $this->renderContentView(
            template: $this->resolveHomeTemplate($item),
            lang: $lang,
            item: $item,
            viewData: [
                'item' => $item,
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
        $item = $this->findContent($modelClass, $lang, $page_slug);

        // Handle localized slug redirects
        if ($item) {
            if ($redirect = $this->maybeRedirectToLocalizedSlug('cms.static.page', $lang, $page_slug, $item, 'page_slug')) {
                return $redirect;
            }
        }

        // Try fallback content model if page not found
        if (!$item) {
            $fallbackResult = $this->tryFallbackContentModel($lang, $page_slug, $request);
            if ($fallbackResult) {
                return $fallbackResult;
            }
            abort(404, "Page not found for slug '{$page_slug}'");
        }

        if (method_exists($this, 'setsSeo')) {
            $this->setsSeo($item);
        }

        return $this->renderContentView(
            template: $this->resolvePageTemplate($item),
            lang: $lang,
            item: $item,
            viewData: [
                'item' => $item,
            ]
        );
    }

    /**
     * Display a single content item by content type and slug
     */
    public function singleContent(Request $request, string $lang, string $content_type_key, string $content_slug)
    {

        // Get the original content type key from the slug (handles custom slug overrides)
        $originalContentTypeKey = $this->getOriginalContentTypeKey($content_type_key);

        // Redirect to static page route if this is a static page
        if ($originalContentTypeKey === 'pages' || $content_type_key === Config::get('cms.static_page_slug')) {
            return redirect()->route('cms.static.page', array_merge(
                ['lang' => $lang, 'page_slug' => $content_slug],
                $request->query()
            ));
        }

        $modelClass = $this->getContentModelClass($content_type_key);
        $item = $this->findContent($modelClass, $lang, $content_slug);

        // Handle localized slug redirects
        if ($item) {
            if ($redirect = $this->maybeRedirectToLocalizedSlug('cms.single.content', $lang, $content_slug, $item, 'content_slug')) {
                return $redirect;
            }
        }

        if (!$item) {
            throw (new ModelNotFoundException)->setModel(
                $modelClass,
                "No content found for slug '{$content_slug}'"
            );
        }

        $this->incrementPageViewsIfSupported($item);

        if (method_exists($this, 'setsSeo')) {
            $this->setsSeo($item);
        }

        return $this->renderContentView(
            template: $this->resolveSingleTemplate($item, $content_type_key, $content_slug),
            lang: $lang,
            item: $item,
            contentTypeKey: $content_type_key,
            contentSlug: $content_slug,
            viewData: [
                'content_type' => $content_type_key,
                'content_slug' => $content_slug,
                'original_content_type' => $originalContentTypeKey,
                'item' => $item,
                'title' => $item->title,
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

        $archive = $this->createArchiveObject($content_type_archive_key, $lang);
        $paginationLimit = $archive->per_page ?? $this->paginationLimit;

        $items = $modelClass::with($eagerLoadRelationships)
            ->where('status', ContentStatus::Published)
            ->orderBy('created_at', 'desc')
            ->paginate($paginationLimit);

        $this->setArchiveSeoMetadata($content_type_archive_key, $archive);

        return $this->renderContentView(
            template: $this->resolveArchiveTemplate($content_type_archive_key),
            lang: $lang,
            item: $archive,
            contentTypeKey: $content_type_archive_key,
            viewData: [
                'post_type' => $content_type_archive_key,
                'archive' => $archive,
                'record' => $archive->static_page ?? null,
                'title' => $archive->static_page->title ?? 'Archive: ' . Str::title(str_replace('-', ' ', $content_type_archive_key)),
                'items' => $items,
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

        $items = $this->getTaxonomyRelatedContent($taxonomyModel, $taxonomy_key);

        if (method_exists($this, 'setsSeo')) {
            $this->setsSeo($taxonomyModel);
        }

        return $this->renderContentView(
            template: $this->resolveTaxonomyTemplate($taxonomy_slug, $taxonomy_key, $taxonomyModel),
            lang: $lang,
            item: $taxonomyModel,
            contentTypeKey: $taxonomy_key,
            contentSlug: $taxonomy_slug,
            viewData: [
                'taxonomy' => $taxonomy_key,
                'taxonomy_slug' => $taxonomy_slug,
                'record' => $taxonomyModel,
                'title' => $taxonomyModel->title ??
                    Str::title(str_replace('-', ' ', $taxonomy_key)) . ': ' .
                    Str::title(str_replace('-', ' ', $taxonomy_slug)),
                'items' => $items,
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

        // Check if the model has a 'status' column
        $hasStatusColumn = \Schema::hasColumn((new $modelClass)->getTable(), 'status');

        // Try the requested locale first
        $query = $modelClass::query();

        if ($hasStatusColumn) {
            $query->where('status', ContentStatus::Published);
        }

        $content = $query->whereJsonContainsLocale('slug', $requestedLocale, $slug)->first();

        // Fallback to default locale if not found
        if (!$content && $requestedLocale !== $defaultLanguage) {
            $query = $modelClass::query();

            if ($hasStatusColumn) {
                $query->where('status', ContentStatus::Published);
            }

            $content = $query->whereJsonContainsLocale('slug', $defaultLanguage, $slug)->first();
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

        $item = $this->findContent($modelClass, $lang, $slug);

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
     * Render a view with common data and body classes
     */
    private function renderContentView(
        string $template,
        string $lang,
        $item = null,
        ?string $contentTypeKey = null,
        ?string $contentSlug = null,
        array $viewData = []
    ) {
        $bodyClasses = $this->generateBodyClasses($lang, $item, $contentTypeKey, $contentSlug);

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
    private function incrementPageViewsIfSupported(Model $item): void
    {
        if (in_array(\Littleboy130491\Sumimasen\Traits\HasPageViews::class, class_uses_recursive($item))) {
            $item->incrementPageViews();
        }
    }

    /**
     * Create an archive object for archive pages, with static page content if configured
     */
    private function createArchiveObject(string $contentTypeKey, string $lang): object
    {
        $originalKey = $this->getOriginalContentTypeKey($contentTypeKey);
        $config = Config::get("cms.content_models.{$originalKey}", []);

        // Check if archive_page_slug is configured
        $pageSlug = $config['archive_page_slug'] ?? null;

        if ($pageSlug) {
            $staticPage = $this->findStaticPageBySlug($pageSlug, $lang);
            if ($staticPage) {
                return $this->createArchiveObjectFromStaticPage($staticPage, $contentTypeKey, $config);
            }
        }

        // Fallback to default archive object
        return $this->createDefaultArchiveObject($contentTypeKey, $config);
    }

    /**
     * Find static page by slug with language fallback
     */
    private function findStaticPageBySlug(string $slug, string $lang): ?\Illuminate\Database\Eloquent\Model
    {
        $modelClass = $this->getValidModelClass($this->staticPageClass);

        // Try requested language first
        $page = $modelClass::where('status', ContentStatus::Published)
            ->whereJsonContainsLocale('slug', $lang, $slug)
            ->first();

        if ($page) {
            return $page;
        }

        // Try default language as fallback
        if ($lang !== $this->defaultLanguage) {
            $page = $modelClass::where('status', ContentStatus::Published)
                ->whereJsonContainsLocale('slug', $this->defaultLanguage, $slug)
                ->first();
        }

        return $page;
    }

    /**
     * Create archive object from static page
     */
    private function createArchiveObjectFromStaticPage($staticPage, string $contentTypeKey, array $config): object
    {
        return (object) [
            'static_page' => $staticPage, // the full page object
            'post_type' => $contentTypeKey,
            'source' => 'static_page',
            'config' => $config,
            'per_page' => $config['per_page'] ?? $this->paginationLimit,
        ];
    }

    /**
     * Create default archive object when no static page is found
     */
    private function createDefaultArchiveObject(string $contentTypeKey, array $config): object
    {
        $name = $config['name'] ?? Str::title(str_replace('-', ' ', $contentTypeKey));

        return (object) [
            'title' => $name,
            'subtitle' => null,
            'description' => 'Archive of all ' . $name . ' content.',
            'item' => null,
            'featured_image' => null,
            'seo_title' => $config['archive_SEO_title'] ?? "Archive: {$name}",
            'seo_description' => $config['archive_SEO_description'] ?? "Archive of all {$name}",
            'post_type' => $contentTypeKey,
            'source' => 'config',
            'static_page' => null,
            'config' => $config,
            'per_page' => $config['per_page'] ?? $this->paginationLimit,
        ];
    }

    /**
     * Set SEO metadata for archive pages with static page support
     */
    private function setArchiveSeoMetadata(string $contentTypeKey, object $archive): void
    {
        $title = $archive->seo_title ?? $archive->static_page->title;
        $description = $archive->seo_description ?? $archive->static_page->excerpt;

        if ($title) {
            SEOTools::setTitle($title);
        }

        if ($description) {
            SEOTools::setDescription($description);
        }

        // Set additional SEO if static page has more data
        if ($archive->static_page && method_exists($this, 'setsSeo')) {
            $this->setsSeo($archive->static_page);
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
        $item = $modelClass::where('status', ContentStatus::Published)
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
    private function redirectToHome(string $lang, Request $request)
    {
        return redirect()->route('cms.home', array_merge(['lang' => $lang], $request->query()));
    }

    // ===== TEMPLATE RESOLUTION METHODS =====

    /**
     * Resolve template for home page with fallback hierarchy
     */
    private function resolveHomeTemplate(?Model $item = null): string
    {
        $templates = [
            "{$this->templateBase}.singles.home",
            "{$this->templateBase}.singles.front-page",
            "{$this->templateBase}.home",
            "{$this->templateBase}.front-page",
            "{$this->templateBase}.singles.default",
            "{$this->templateBase}.default",
        ];

        if ($item) {
            $customTemplates = $this->getContentCustomTemplates($item);
            $templates = array_merge($customTemplates, $templates);
        }

        return $this->findFirstExistingTemplate($templates);
    }

    /**
     * Resolve template for static pages with slug-specific and custom templates
     */
    private function resolvePageTemplate(Model $item): string
    {
        $slug = $item->slug;
        $defaultSlug = method_exists($item, 'getTranslation') ?
            ($item->getTranslation('slug', $this->defaultLanguage) ?? $slug) : $slug;

        $templates = [
            "{$this->templateBase}.singles.{$defaultSlug}",
            "{$this->templateBase}.singles.page",
            "{$this->templateBase}.page",
            "{$this->templateBase}.singles.default",
            "{$this->templateBase}.default",
        ];

        $customTemplates = $this->getContentCustomTemplates($item);
        $templates = array_merge($customTemplates, $templates);

        return $this->findFirstExistingTemplate($templates);
    }

    /**
     * Resolve template for single content with support for custom slugs and fallbacks
     */
    private function resolveSingleTemplate(?Model $item, string $content_type_key, string $contentSlug): string
    {
        $postTypeFromSlug = Str::kebab(Str::singular($content_type_key));
        $originalContentTypeKey = $this->getOriginalContentTypeKey($content_type_key);
        $postTypeFromKey = Str::kebab(Str::singular($originalContentTypeKey));

        $defaultSlug = method_exists($item, 'getTranslation') ?
            ($item->getTranslation('slug', $this->defaultLanguage) ?? $contentSlug) : $contentSlug;

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

        if ($item) {
            $customTemplates = $this->getContentCustomTemplates($item);
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
            "{$this->templateBase}.archives.archive-{$originalKey}",
            "{$this->templateBase}.archive-{$content_type_archive_key}",
            "{$this->templateBase}.archive-{$originalKey}",
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
    private function getContentCustomTemplates(Model $item): array
    {
        $templates = [];

        if (!empty($item->template)) {
            $templates[] = "{$this->templateBase}.{$item->template}";
        }

        if (method_exists($item, 'getTranslation')) {
            $defaultSlug = $item->getTranslation('slug', $this->defaultLanguage);
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
    private function generateBodyClasses(string $lang, $item = null, ?string $contentTypeKey = null, ?string $contentSlug = null): string
    {
        $classes = ["lang-{$lang}"];

        if ($item) {
            if ($item instanceof Model) {
                $classes[] = 'type-' . ($contentTypeKey ?? Str::kebab(Str::singular($item->getTable())));

                $slugForClass = '';
                if (method_exists($item, 'getTranslation')) {
                    $slugForClass = $item->slug ?? $contentSlug;
                } else {
                    $slugForClass = $contentSlug;
                }

                if ($slugForClass) {
                    $classes[] = 'slug-' . $slugForClass;
                }

                if (!empty($item->template)) {
                    $classes[] = 'template-' . Str::kebab($item->template);
                }
            } elseif (is_object($item)) {
                if (isset($item->post_type)) {
                    $classes[] = 'archive-' . $item->post_type;
                } elseif (isset($item->taxonomy)) {
                    $classes[] = 'taxonomy-' . $item->taxonomy;
                    if (isset($item->taxonomy_slug)) {
                        $classes[] = 'term-' . $item->taxonomy_slug;
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
    private function getEagerLoadRelationships(string $content_type_key): array
    {
        $originalKey = $this->getOriginalContentTypeKey($content_type_key);
        $config = config("cms.content_models.{$originalKey}");

        return $config['eager_load'] ?? [];
    }
}
