<?php

namespace Littleboy130491\Sumimasen\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class SingleContentController extends BaseContentController
{
    /**
     * Display a single content item by content type and slug
     */
    public function __invoke(Request $request, string $lang, string $content_type_key, string $content_slug)
    {
        // Check if preview mode is enabled
        $isPreview = $request->query('preview') === 'true';

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
        $item = $this->findContent($modelClass, $lang, $content_slug, $isPreview);

        // Handle localized slug redirects
        if ($item) {
            if ($redirect = $this->maybeRedirectToLocalizedSlug('cms.single.content', $lang, $content_slug, $item, 'content_slug')) {
                return $redirect;
            }
        }

        if (! $item) {
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
            ],
            isPreview: $isPreview
        );
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
}
