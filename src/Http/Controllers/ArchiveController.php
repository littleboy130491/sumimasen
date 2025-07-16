<?php

namespace Littleboy130491\Sumimasen\Http\Controllers;

use Artesaos\SEOTools\Facades\SEOTools;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Littleboy130491\Sumimasen\Models\Archive;

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
        $paginationLimit = config('cms.content_models.' . $originalKey . '.per_page') ?? $this->paginationLimit;

        $items = $this->buildQueryWithStatusFilter($modelClass)
            ->with($eagerLoadRelationships)
            ->orderBy('created_at', 'desc')
            ->paginate($paginationLimit);


        return $this->renderContentView(
            template: $this->resolveArchiveTemplate($content_type_archive_key),
            lang: $lang,
            item: $archive,
            contentTypeKey: $content_type_archive_key,
            viewData: [
                'post_type' => $content_type_archive_key,
                'archive' => $archive,
                'title' => $archive->archive->title ?? $archive->title,
                'description' => $archive->archive->content ?? $archive->content,
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
            $archive = $this->findArchiveBySlug($pageSlug);
            if ($archive) {
                return $this->createArchiveObjectFromArchive($archive, $contentTypeKey, $config);
            }
        }

        // Try to find archive by originalKey if pageSlug didn't work
        $archive = $this->findArchiveBySlug($originalKey);
        if ($archive) {
            return $this->createArchiveObjectFromArchive($archive, $contentTypeKey, $config);
        }

        // Fallback to default archive object
        return $this->createDefaultArchiveObject($contentTypeKey, $config);
    }

    /**
     * Find archive by slug with language fallback
     */
    private function findArchiveBySlug(string $slug): ?Archive
    {
        // Try requested language first
        $archive = Archive::whereJsonContainsLocale('slug', $this->defaultLanguage, $slug)->first();

        if ($archive) {
            return $archive;
        }

        return null;
    }

    /**
     * Create archive object from Archive model
     */
    private function createArchiveObjectFromArchive(Archive $archive, string $contentTypeKey, array $config): object
    {
        // set SEO from CMS
        if (method_exists($this, 'setsSeo')) {
            $this->setsSeo($archive);
        }

        return (object) [
            'archive' => $archive, // the full archive object
            'post_type' => $contentTypeKey,
            'source' => 'archive',
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

        // set SEO manually
        $title = $config['archive_SEO_title'] ?? "Archive: {$name}";
        $description = $config['archive_SEO_description'] ?? "Archive of all {$name}";

        SEOTools::setTitle($title);
        SEOTools::setDescription($description);

        return (object) [
            'title' => $title,
            'content' => $description,
            'post_type' => $contentTypeKey,
            'source' => 'config',
            'config' => $config,
            'per_page' => $config['per_page'] ?? $this->paginationLimit,
        ];
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
