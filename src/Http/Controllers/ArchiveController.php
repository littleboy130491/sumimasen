<?php

namespace Littleboy130491\Sumimasen\Http\Controllers;

use Artesaos\SEOTools\Facades\SEOTools;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class ArchiveController extends BaseContentController
{
    /**
     * Display an archive page listing all content of a specific type
     */
    public function __invoke(string $lang, string $content_type_archive_key)
    {
        $modelClass = $this->getContentModelClass($content_type_archive_key);
        $originalKey = $this->getOriginalContentTypeKey($content_type_archive_key);
        $eagerLoadRelationships = $this->getEagerLoadRelationships($originalKey);

        $archive = $this->createArchiveObject($content_type_archive_key, $lang);
        $paginationLimit = config('cms.content_models.'.$originalKey.'.per_page') ?? $this->paginationLimit;

        $items = $this->buildQueryWithStatusFilter($modelClass)
            ->with($eagerLoadRelationships)
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
                'title' => $archive->static_page->title ?? 'Archive: '.Str::title(str_replace('-', ' ', $content_type_archive_key)),
                'items' => $items,
            ]
        );
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
        $page = $this->buildQueryWithStatusFilter($modelClass)
            ->whereJsonContainsLocale('slug', $lang, $slug)
            ->first();

        if ($page) {
            return $page;
        }

        // Try default language as fallback
        if ($lang !== $this->defaultLanguage) {
            $page = $this->buildQueryWithStatusFilter($modelClass)
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
            'description' => 'Archive of all '.$name.' content.',
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
}
