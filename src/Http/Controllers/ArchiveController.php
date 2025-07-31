<?php

namespace Littleboy130491\Sumimasen\Http\Controllers;

use Artesaos\SEOTools\Facades\SEOTools;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\View\View as ViewResponse;
use Littleboy130491\Sumimasen\Models\Archive;

class ArchiveController extends BaseContentController
{
    /**
     * Display an archive page listing all content of a specific type
     */
    public function __invoke(string $lang, string $slug)
    {
        // Determine which Archive class to use
        $archiveClass = class_exists('App\\Models\\Archive') ? \App\Models\Archive::class : Archive::class;

        // Try to find archive page in database
        $archiveModel = $this->findArchive($archiveClass, $lang, $slug);

        // Handle localized redirects if needed
        if ($archiveModel && $this->shouldRedirectToLocalizedSlug) {
            return $this->redirectToLocalizedSlug($lang, $archiveModel, 'slug');
        }

        // Determine the content type key to work with
        $contentTypeKey = $this->determineContentTypeKey($archiveModel, $lang, $slug);

        // Find the original config key from $contentTypeKey
        $originalConfigKey = $this->findOriginalConfigKey($contentTypeKey);

        // Create archive object for display
        $archiveObject = $archiveModel
            ? $this->createArchiveObjectFromModel($archiveModel, $contentTypeKey, $originalConfigKey)
            : $this->createArchiveObject($contentTypeKey, $originalConfigKey, $lang);

        // Step 6: Get content items for this archive
        $items = $this->getArchiveItems($originalConfigKey);

        // Step 7: Render the archive view
        return $this->renderArchiveView($lang, $archiveObject, $items, $contentTypeKey);
    }

    /**
     * Determine content type key from archive model or slug
     */
    private function determineContentTypeKey(?Model $archiveModel, string $lang, string $slug): string
    {
        if (!$archiveModel) {
            return $slug;
        }

        // Get slug from default language if we have a model
        if ($lang !== $this->defaultLanguage && method_exists($archiveModel, 'getTranslation')) {
            return $archiveModel->getTranslation('slug', $this->defaultLanguage, false) ?: $archiveModel->slug;
        }

        return $archiveModel->slug;
    }

    /**
     * Find the original config key by checking content_models configuration
     * First check if any config has a 'slug' that matches, otherwise use the key itself
     */
    private function findOriginalConfigKey(string $contentTypeKey): string
    {
        $contentModels = Config::get('cms.content_models', []);

        // First, check if any config has a 'slug' that matches our contentTypeKey
        foreach ($contentModels as $configKey => $config) {
            if (isset($config['slug']) && $config['slug'] === $contentTypeKey) {
                return $configKey;
            }
        }

        // If no config with matching 'slug' found, check if the key itself exists in config
        if (isset($contentModels[$contentTypeKey])) {
            return $contentTypeKey;
        }

        // Fallback: return the contentTypeKey as-is
        return $contentTypeKey;
    }

    /**
     * Get paginated items for the archive based on config
     */
    private function getArchiveItems(string $originalConfigKey)
    {
        $config = Config::get("cms.content_models.{$originalConfigKey}", []);

        // For regular content types
        return $this->getContentItems($originalConfigKey, $config);
    }

    /**
     * Get items for regular content archives
     */
    private function getContentItems(string $originalConfigKey, array $config)
    {
        $modelClass = $config['model'] ?? null;
        if (!$modelClass) {
            return collect();
        }

        $paginationLimit = $config['per_page'] ?? $this->paginationLimit;
        $eagerLoadRelationships = $config['eager_load'] ?? [];

        return $this->buildQueryWithStatusFilter($modelClass)
            ->with($eagerLoadRelationships)
            ->orderBy('created_at', 'desc')
            ->paginate($paginationLimit);
    }

    /**
     * Create archive object from existing Archive model
     */
    private function createArchiveObjectFromModel($archive, string $contentTypeKey, string $originalConfigKey): object
    {
        $config = Config::get("cms.content_models.{$originalConfigKey}", []);

        // Set SEO from CMS if method exists
        if (method_exists($this, 'setsSeo')) {
            $this->setsSeo($archive);
        }

        return (object) [
            'record' => $archive,
            'post_type' => $contentTypeKey,
            'original_key' => $originalConfigKey,
            'source' => 'archive',
            'config' => $config,
            'per_page' => $config['per_page'] ?? $this->paginationLimit,
        ];
    }

    /**
     * Create archive object when no Archive model exists - purely from config
     */
    private function createArchiveObject(string $contentTypeKey, string $originalConfigKey, string $lang): object
    {
        $config = Config::get("cms.content_models.{$originalConfigKey}", []);

        // Check if this config has archive enabled
        if (!($config['has_archive'] ?? false)) {
            abort(404);
        }

        // Validate the requested slug against config
        $this->validateArchiveSlug($contentTypeKey, $originalConfigKey, $config);

        // Create default archive object from config
        return $this->createDefaultArchiveObject($contentTypeKey, $originalConfigKey, $config);
    }

    /**
     * Validate if the requested slug is allowed based on config
     */
    private function validateArchiveSlug(string $contentTypeKey, string $originalConfigKey, array $config): void
    {
        // If config has a specific 'slug' defined, only that slug is allowed
        if (isset($config['slug'])) {
            if ($contentTypeKey !== $config['slug']) {
                abort(404); // Wrong slug - should be using the configured slug
            }
        } else {
            // If no specific slug in config, only the config key itself is allowed
            if ($contentTypeKey !== $originalConfigKey) {
                abort(404); // Wrong slug - should be using the config key
            }
        }
    }

    /**
     * Create default archive object when no static page is found
     */
    private function createDefaultArchiveObject(string $contentTypeKey, string $originalConfigKey, array $config): object
    {
        $name = $config['name'] ?? Str::title(str_replace('-', ' ', $contentTypeKey));
        $title = $config['archive_SEO_title'] ?? "Archive: {$name}";
        $description = $config['archive_SEO_description'] ?? "Archive of all {$name}";

        // Set SEO manually
        SEOTools::setTitle($title);
        SEOTools::setDescription($description);

        // Create a mock record object
        $mockRecord = (object) [
            'title' => $title,
            'content' => $description,
            'slug' => $contentTypeKey,
        ];

        return (object) [
            'record' => $mockRecord,
            'title' => $title,
            'content' => $description,
            'post_type' => $contentTypeKey,
            'original_key' => $originalConfigKey,
            'source' => 'config',
            'config' => $config,
            'per_page' => $config['per_page'] ?? $this->paginationLimit,
        ];
    }

    /**
     * Render the archive view with all data
     */
    private function renderArchiveView(string $lang, object $archiveObject, $items, string $contentTypeKey): ViewResponse
    {
        $template = $this->resolveArchiveTemplate($contentTypeKey, $archiveObject->original_key);

        return $this->renderContentView(
            template: $template,
            lang: $lang,
            item: $archiveObject->record,
            contentTypeKey: $contentTypeKey,
            viewData: [
                'archive' => $archiveObject,
                'record' => $archiveObject->record,
                'title' => $this->getArchiveTitle($archiveObject),
                'description' => $this->getArchiveDescription($archiveObject),
                'items' => $items,
            ]
        );
    }

    /**
     * Get archive title with fallbacks
     */
    private function getArchiveTitle(object $archiveObject): string
    {
        return $archiveObject->record->title
            ?? $archiveObject->title
            ?? '';
    }

    /**
     * Get archive description with fallbacks
     */
    private function getArchiveDescription(object $archiveObject): string
    {
        return $archiveObject->record->content
            ?? $archiveObject->content
            ?? '';
    }

    /**
     * Resolve template for archive pages
     */
    private function resolveArchiveTemplate(string $contentTypeKey, string $originalConfigKey): string
    {
        $configView = Config::get("cms.content_models.{$originalConfigKey}.archive_view");

        // Check for configured custom view first
        if ($configView && View::exists($configView)) {
            return $configView;
        }

        // Template fallback hierarchy
        $templates = [
            "{$this->templateBase}.archives.archive-{$contentTypeKey}",
            "{$this->templateBase}.archives.archive-{$originalConfigKey}",
            "{$this->templateBase}.archive-{$contentTypeKey}",
            "{$this->templateBase}.archive-{$originalConfigKey}",
            "{$this->templateBase}.archives.archive",
            "{$this->templateBase}.archive",
        ];

        return $this->findFirstExistingTemplate($templates);
    }

    /**
     * Find archive with language fallback and redirect logic
     */
    protected function findArchive(string $modelClass, string $requestedLocale, string $slug, bool $isPreview = false): ?Model
    {
        $defaultLanguage = $this->defaultLanguage;

        // Try the requested locale first
        $content = $modelClass::whereJsonContainsLocale('slug', $requestedLocale, $slug)->first();

        // Fallback to default locale if not found
        if (!$content && $requestedLocale !== $defaultLanguage) {
            $content = $modelClass::whereJsonContainsLocale('slug', $defaultLanguage, $slug)->first();

            // Set redirect flag if localized slug differs from requested slug
            if ($content && $content->slug !== $slug) {
                $this->shouldRedirectToLocalizedSlug = true;
            }
        }

        return $content;
    }
}
