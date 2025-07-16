<?php

namespace Littleboy130491\Sumimasen\Http\Controllers;

class HomeController extends BaseContentController
{
    /**
     * Display the home page content - finds home page by slug or first available page
     */
    public function __invoke(string $lang)
    {
        $modelClass = $this->getValidModelClass($this->staticPageClass);

        // Find home page by configured slug
        $item = $this->buildQueryWithStatusFilter($modelClass)
            ->whereJsonContainsLocale('slug', $this->defaultLanguage, $this->frontPageSlug)
            ->first();

        // Fallback to first available page if home not found
        if (! $item) {
            $item = $this->buildQueryWithStatusFilter($modelClass)
                ->orderBy('id', 'asc')
                ->first();

            if (! $item) {
                abort(404, 'Home page content not found.');
            }
        }

        $this->incrementPageViewsIfSupported($item);

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
     * Resolve template for home page with fallback hierarchy
     */
    private function resolveHomeTemplate($item = null): string
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
}
