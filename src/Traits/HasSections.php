<?php

namespace Littleboy130491\Sumimasen\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Awcodes\Curator\Models\Media;

trait HasSections
{
    protected function section(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                // Get the raw JSON translations from attributes
                $translations = json_decode($attributes['section'] ?? '{}', true);

                // Get current locale
                $currentLocale = $this->getLocale();

                // Get current locale's value
                $currentValue = $translations[$currentLocale] ?? [];

                // If current locale is empty, use fallback
                if (empty($currentValue)) {
                    // Try default language
                    $defaultLocale = config('cms.default_language');

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
                    // if this block has an "media_id" key, fetch its URL
                    if (isset($block['data']['media_id'])) {
                        $media = Media::find($block['data']['media_id']);
                        $block['data']['media_url'] = $media?->url;
                    }

                    return $block;
                })->all();
            }
        );
    }
}
