<?php

namespace Littleboy130491\Sumimasen\Traits;

use Awcodes\Curator\Models\Media;

trait HasSections
{
    /**
     * Get sections with fallback and media URLs for frontend
     */
    public function getSectionsForFrontend(): array
    {
        // Get all translations for the section field
        $translations = $this->getTranslations('section');

        // Get current locale
        $currentLocale = app()->getLocale();

        // Get current locale's value
        $currentValue = $translations[$currentLocale] ?? [];

        // If current locale is empty, use fallback
        if (empty($currentValue)) {
            // Try default language
            $defaultLocale = config('cms.default_language', config('app.fallback_locale'));

            if (isset($translations[$defaultLocale]) && ! empty($translations[$defaultLocale])) {
                $currentValue = $translations[$defaultLocale];
            } else {
                // Return first non-empty translation
                foreach ($translations as $locale => $localeValue) {
                    if (! empty($localeValue)) {
                        $currentValue = $localeValue;
                        break;
                    }
                }
            }
        }

        // Inject media URLs into blocks
        return collect($currentValue)->map(function (array $block) {
            if (isset($block['data']['image'])) {
                $media = Media::find($block['data']['image']);
                $block['data']['media_url'] = $media?->url;
            }

            return $block;
        })->all();
    }

    /**
     * Alias for frontend sections as 'block'
     */
    public function getBlockAttribute(): array
    {
        return $this->getSectionsForFrontend();
    }
}
