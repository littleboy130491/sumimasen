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
            // Handle single image (integer ID from CuratorPicker)
            if (isset($block['data']['image']) && is_int($block['data']['image'])) {
                $media = Media::find($block['data']['image']);
                $block['data']['media_url'] = $media?->path;
            }

            // Handle single media field
            if (isset($block['data']['media']) && is_int($block['data']['media'])) {
                $media = Media::find($block['data']['media']);
                $block['data']['media_url'] = $media?->path;
            }

            // Handle logo field (for tab blocks)
            if (isset($block['data']['logo']) && is_int($block['data']['logo'])) {
                $logoMedia = Media::find($block['data']['logo']);
                $block['data']['logo_url'] = $logoMedia?->path;
            }

            // Handle gallery with embedded media objects (from CuratorPicker with multiple())
            // This comes as an object with UUID keys containing full media data
            if (isset($block['data']['image']) && is_array($block['data']['image']) && !empty($block['data']['image'])) {
                // Check if it's an associative array with UUID keys (gallery format)
                $firstKey = array_key_first($block['data']['image']);
                if ($firstKey && !is_numeric($firstKey)) {
                    // Extract URLs from embedded media objects
                    $block['data']['gallery_urls'] = collect($block['data']['image'])
                        ->pluck('url')
                        ->filter()
                        ->values()
                        ->toArray();
                }
            }

            // Handle multiple images using gallery array (array of IDs)
            if (isset($block['data']['gallery']) && is_array($block['data']['gallery'])) {
                $mediaIds = $block['data']['gallery'];
                $mediaItems = Media::whereIn('id', $mediaIds)->get()->keyBy('id');

                // Map gallery IDs to their URLs, preserving order
                $galleryUrls = collect($mediaIds)
                    ->map(fn($id) => $mediaItems->get($id)?->path)
                    ->filter() // Remove null values
                    ->values()
                    ->toArray();

                // Only override if we don't already have gallery_urls from image field
                if (empty($block['data']['gallery_urls'])) {
                    $block['data']['gallery_urls'] = $galleryUrls;
                }
            }

            return $block;
        })->toArray();
    }

    /**
     * Alias for frontend sections as 'block'
     */
    public function getBlockAttribute(): array
    {
        return $this->getSectionsForFrontend();
    }
}