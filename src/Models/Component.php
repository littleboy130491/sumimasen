<?php

namespace Littleboy130491\Sumimasen\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Component extends Model
{
    use HasTranslations, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'data',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array<int, string>
     */
    public $translatable = [
        'data',
    ];

    /**
     * Get the section attribute with fallback for empty values
     */
    protected function data(): Attribute
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

                    if (isset($translations[$defaultLocale]) && ! empty($translations[$defaultLocale])) {
                        return $translations[$defaultLocale];
                    }

                    // Return first non-empty translation
                    foreach ($translations as $locale => $localeValue) {
                        if (! empty($localeValue)) {
                            return $localeValue;
                        }
                    }
                }

                return $currentValue;
            }
        );
    }
}
