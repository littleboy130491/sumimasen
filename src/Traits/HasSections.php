<?php

namespace Littleboy130491\Sumimasen\Traits;

use Awcodes\Curator\Models\Media;

trait HasSections
{
    /**
     * Generate URL from media path for portability across domains
     */
    private function generateMediaUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }
        
        return url($path);
    }

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
                $block['data']['media_url'] = $this->generateMediaUrl($media?->path);
            } else {
                // Clear media_url if image field is deleted or not an integer
                unset($block['data']['media_url']);
            }

            // Handle single media field
            if (isset($block['data']['media']) && is_int($block['data']['media'])) {
                $media = Media::find($block['data']['media']);
                $block['data']['media_url'] = $this->generateMediaUrl($media?->path);
            } else {
                // Clear media_url if media field is deleted or not an integer
                unset($block['data']['media_url']);
            }

            // Handle logo field (for tab blocks)
            if (isset($block['data']['logo']) && is_int($block['data']['logo'])) {
                $logoMedia = Media::find($block['data']['logo']);
                $block['data']['logo_url'] = $this->generateMediaUrl($logoMedia?->path);
            } else {
                // Clear logo_url if logo field is deleted or not an integer
                unset($block['data']['logo_url']);
            }

            // Handle gallery with embedded media objects (from CuratorPicker with multiple())
            // This comes as an object with UUID keys containing full media data
            if (isset($block['data']['image']) && is_array($block['data']['image']) && !empty($block['data']['image'])) {
                // Check if it's an associative array with UUID keys (gallery format)
                $firstKey = array_key_first($block['data']['image']);
                if ($firstKey && !is_numeric($firstKey)) {
                    // Extract URLs from embedded media objects using path
                    $block['data']['gallery_urls'] = collect($block['data']['image'])
                        ->map(fn($media) => $this->generateMediaUrl($media['path'] ?? null))
                        ->filter()
                        ->values()
                        ->toArray();
                }
            } else {
                // Clear gallery_urls if image field is deleted, empty, or not an array
                unset($block['data']['gallery_urls']);
            }

            // Handle multiple images using gallery array (array of IDs)
            if (isset($block['data']['gallery']) && is_array($block['data']['gallery'])) {
                $mediaIds = $block['data']['gallery'];
                $mediaItems = Media::whereIn('id', $mediaIds)->get()->keyBy('id');

                // Map gallery IDs to their URLs using path, preserving order
                $galleryUrls = collect($mediaIds)
                    ->map(fn($id) => $this->generateMediaUrl($mediaItems->get($id)?->path))
                    ->filter() // Remove null values
                    ->values()
                    ->toArray();

                // Always update gallery_urls to match the current gallery state
                // This ensures that if gallery is emptied, gallery_urls is also emptied
                $block['data']['gallery_urls'] = $galleryUrls;
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