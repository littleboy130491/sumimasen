<?php

namespace Littleboy130491\Sumimasen\Models;

use Awcodes\Curator\Models\Media;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Littleboy130491\SeoSuite\Models\Traits\InteractsWithSeoSuite;
use Spatie\Translatable\HasTranslations;

class Archive extends Model
{
    use HasFactory, HasTranslations, InteractsWithSeoSuite, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'slug',
        'content',
        'section',
        'featured_image',
        'template',
        'custom_fields',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'section' => 'array',
        'custom_fields' => 'array',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array<int, string>
     */
    public $translatable = [
        'title',
        'slug',
        'content',
        'section',
    ];

    /**
     * Get the section attribute with fallback for empty values
     */
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

    // --------------------------------------------------------------------------
    // Relationships
    // --------------------------------------------------------------------------

    /**
     * Define the featuredImage relationship to Curator Media.
     */
    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_image', 'id');
    }
}
