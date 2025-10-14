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

            if (isset($translations[$defaultLocale]) && !empty($translations[$defaultLocale])) {
                $currentValue = $translations[$defaultLocale];
            } else {
                // Return first non-empty translation
                foreach ($translations as $locale => $localeValue) {
                    if (!empty($localeValue)) {
                        $currentValue = $localeValue;
                        break;
                    }
                }
            }
        }

        // Inject media URLs into blocks
        return collect($currentValue)->map(function (array $block) {
            // Handle single image
            if (isset($block['data']['image'])) {
                $media = Media::find($block['data']['image']);
                $block['data']['media_url'] = $media?->url;
            }

            // Handle single media
            if (isset($block['data']['media'])) {
                $media = Media::find($block['data']['media']);
                $block['data']['media_url'] = $media?->url;
            }

            // Handle multiple images (gallery)
            if (isset($block['data']['gallery']) && is_array($block['data']['gallery'])) {
                $mediaIds = $block['data']['gallery'];
                $mediaItems = Media::whereIn('id', $mediaIds)->get()->keyBy('id');

                // Map gallery IDs to their URLs, preserving order
                $block['data']['gallery_urls'] = collect($mediaIds)
                    ->map(fn($id) => $mediaItems->get($id)?->url)
                    ->filter() // Remove null values
                    ->values()
                    ->all();
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