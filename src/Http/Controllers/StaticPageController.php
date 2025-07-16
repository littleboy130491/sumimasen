<?php

namespace Littleboy130491\Sumimasen\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class StaticPageController extends BaseContentController
{
    /**
     * Display a static page by slug with front page redirect and fallback handling
     */
    public function __invoke(Request $request, string $lang, string $page_slug)
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
        if (! $item) {
            $fallbackResult = $this->tryFallbackContentModel($lang, $page_slug, $request);
            if ($fallbackResult) {
                return $fallbackResult;
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
            ]
        );
    }

    /**
     * Find static page by slug with language fallback
     */
    private function findStaticPageBySlug(string $slug, string $lang): ?Model
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
