<?php

namespace Littleboy130491\Sumimasen\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Littleboy130491\Sumimasen\Models\Archive;

class StaticPageController extends BaseContentController
{
    /**
     * Display a static page by slug with front page redirect and fallback handling
     */
    public function __invoke(Request $request, string $lang, string $page_slug)
    {
        // Check if preview mode is enabled
        $isPreview = $request->query('preview') === 'true';

        // Redirect to home if this is the front page
        if ($this->isFrontPage($lang, $page_slug)) {
            return $this->redirectToHome($lang, $request);
        }

        $modelClass = $this->getValidModelClass($this->staticPageClass);
        $item = $this->findContent($modelClass, $lang, $page_slug, $isPreview);

        // Handle localized slug redirects
        if ($item) {
            // dd($this->shouldRedirectToLocalizedSlug);
            if ($this->shouldRedirectToLocalizedSlug) {
                return $this->redirectToLocalizedSlug($lang, $item, 'page_slug');
            }
        }

        // Try fallback content model if page not found
        if (! $item) {
            $fallbackResult = $this->tryFallbackContentModel($lang, $page_slug, $request, $isPreview);
            if ($fallbackResult) {
                return $fallbackResult;
            }
            // check archive
            $archive = Archive::whereJsonContainsLocale('slug', $lang, $page_slug)->first();
            // Archive::whereJsonContainsLocale('slug', $this->defaultLanguage, $page_slug)->first();
            if ($archive) {
                $slug = $archive->getTranslation('slug', $this->defaultLanguage, false);
                dd($slug);
            }

            abort(404, "Page not found for slug '{$page_slug}'");
        }

        $this->incrementPageViewsIfSupported($item);

        if (method_exists($this, 'setsSeo')) {
            $this->setsSeo($item);
        }

        return $this->renderContentView(
            template: $this->resolvePageTemplate($item),
            lang: $lang,
            item: $item,
            viewData: [
                'item' => $item,
            ],
            isPreview: $isPreview
        );
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
}
