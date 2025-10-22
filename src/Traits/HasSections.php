<?php

namespace Littleboy130491\Sumimasen\Traits;

use Awcodes\Curator\Models\Media;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

trait HasSections
{
    /**
     * Get sections with fallback and media URLs for frontend (batched)
     */
    public function getSectionsForFrontend(): array
    {
        // 1) resolve translation with your same fallback rules
        $translations = $this->getTranslations('section');
        $currentLocale = app()->getLocale();
        $currentValue = $translations[$currentLocale] ?? [];

        if (empty($currentValue)) {
            $defaultLocale = config('cms.default_language', config('app.fallback_locale'));
            if (!empty($translations[$defaultLocale] ?? [])) {
                $currentValue = $translations[$defaultLocale];
            } else {
                foreach ($translations as $locale => $localeValue) {
                    if (!empty($localeValue)) {
                        $currentValue = $localeValue;
                        break;
                    }
                }
            }
        }

        // 2) collect ALL media ids once
        $blocks = collect($currentValue);

        $singleIds = $blocks
            ->map(fn($b) => Arr::get($b, 'data.image'))
            ->filter(fn($v) => is_int($v));

        $mediaIds = $blocks
            ->flatMap(fn($b) => (array) Arr::get($b, 'data.gallery', []))
            ->filter(fn($v) => is_int($v));

        $logoIds = $blocks
            ->map(fn($b) => Arr::get($b, 'data.logo'))
            ->filter(fn($v) => is_int($v));

        $otherSingle = $blocks
            ->map(fn($b) => Arr::get($b, 'data.media'))
            ->filter(fn($v) => is_int($v));

        $allIds = $singleIds
            ->merge($mediaIds)
            ->merge($logoIds)
            ->merge($otherSingle)
            ->unique()
            ->values();

        // 3) one query; keep it light
        $mediaMap = $allIds->isEmpty()
            ? collect()
            : Media::query()
                ->whereIn('id', $allIds)
                ->select(['id', 'disk', 'path']) // url accessor can build from these
                ->get()
                ->keyBy('id');

        // 4) map URLs back into blocks
        return $blocks->map(function (array $block) use ($mediaMap) {
            // single image
            if (isset($block['data']['image']) && is_int($block['data']['image'])) {
                $m = $mediaMap->get($block['data']['image']);
                $block['data']['image_url'] = $m?->url;
            }

            // single media
            if (isset($block['data']['media']) && is_int($block['data']['media'])) {
                $m = $mediaMap->get($block['data']['media']);
                $block['data']['media_url'] = $m?->url;
            }

            // logo (bug fix: use $logoMedia instead of $media)
            if (isset($block['data']['logo']) && is_int($block['data']['logo'])) {
                $logoMedia = $mediaMap->get($block['data']['logo']);
                $block['data']['logo_url'] = $logoMedia?->url;
            }

            // image field already contains embedded curator objects (uuid keys)
            if (isset($block['data']['image']) && is_array($block['data']['image']) && !empty($block['data']['image'])) {
                $firstKey = array_key_first($block['data']['image']);
                if ($firstKey && !is_numeric($firstKey)) {
                    $block['data']['image_urls'] = collect($block['data']['image'])
                        ->pluck('url')
                        ->filter()
                        ->values()
                        ->toArray();
                }
            }

            // gallery = array of IDs → map in original order
            if (isset($block['data']['gallery']) && is_array($block['data']['gallery'])) {
                $ids = $block['data']['gallery'];
                $galleryUrls = collect($ids)
                    ->map(fn($id) => $mediaMap->get($id)?->url)
                    ->filter()
                    ->values()
                    ->toArray();

                if (empty($block['data']['gallery_urls'])) {
                    $block['data']['gallery_urls'] = $galleryUrls;
                }
            }

            return $block;
        })->toArray();
    }

    public function getBlockAttribute(): array
    {
        return $this->getSectionsForFrontend();
    }
}
