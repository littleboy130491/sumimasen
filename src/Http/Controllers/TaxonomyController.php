<?php

namespace Littleboy130491\Sumimasen\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class TaxonomyController extends BaseContentController
{
    /**
     * Display a taxonomy archive page showing all content related to a specific taxonomy term
     */
    public function __invoke(string $lang, string $taxonomy_key, string $taxonomy_slug)
    {
        $modelClass = $this->getContentModelClass($taxonomy_key);
        $taxonomyModel = $this->findContent($modelClass, $lang, $taxonomy_slug);

        // Handle localized slug redirects
        if ($taxonomyModel) {
            if ($this->shouldRedirectToLocalizedSlug) {
                return $this->redirectToLocalizedSlug($lang, $taxonomyModel, 'taxonomy_slug');
            }
        }

        if (! $taxonomyModel) {
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
                    Str::title(str_replace('-', ' ', $taxonomy_key)).': '.
                    Str::title(str_replace('-', ' ', $taxonomy_slug)),
                'items' => $items,
            ]
        );
    }

    /**
     * Get content related to a taxonomy term with pagination
     */
    private function getTaxonomyRelatedContent(Model $taxonomyModel, string $taxonomyKey)
    {
        $originalKey = $this->getOriginalContentTypeKey($taxonomyKey);
        $relationshipName = Config::get("cms.content_models.{$originalKey}.display_content_from", 'posts');

        if (! method_exists($taxonomyModel, $relationshipName)) {
            \Illuminate\Support\Facades\Log::warning("Configured relationship '{$relationshipName}' not found for taxonomy '{$taxonomyKey}'. Falling back to 'posts'.");
            $relationshipName = 'posts';
        }

        if (method_exists($taxonomyModel, $relationshipName)) {
            // Get eager load relationships for the related content type
            $relatedContentEagerLoad = $this->getEagerLoadRelationships($relationshipName);

            $paginationLimit = config('cms.content_models.'.$originalKey.'.per_page') ?? $this->paginationLimit;

            return $taxonomyModel->{$relationshipName}()
                ->with($relatedContentEagerLoad)
                ->orderBy('created_at', 'desc')
                ->paginate($paginationLimit);
        }

        \Illuminate\Support\Facades\Log::warning("Relationship method '{$relationshipName}' ultimately not found for taxonomy '{$taxonomyKey}'. Serving empty collection.");

        return collect();
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
}
