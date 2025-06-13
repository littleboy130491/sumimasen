<?php

namespace Afatmustafa\SeoSuite\Models;

use Afatmustafa\SeoSuite\Enums\OpenGraphTypes;
use Afatmustafa\SeoSuite\Enums\XCardTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoSuite extends Model
{
    use Accessors;

    public $table = 'seo_suite';

    public $fillable = [
        'title',
        'description',
        'canonical_url',
        'metas',
        'og_title',
        'og_description',
        'og_type',
        'og_type_details',
        'og_properties',
        'x_card_type',
        'x_title',
        'x_site',
        'noindex',
        'nofollow',
    ];

    protected $casts = [
        'metas' => 'json',
        'og_type_details' => 'json',
        'og_properties' => 'json',
        'noindex' => 'boolean',
        'nofollow' => 'boolean',
        'og_type' => OpenGraphTypes::class,
        'x_card_type' => XCardTypes::class,
    ];

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        return config('seo-suite.table_name', parent::getTable());
    }

    protected static ?array $seoFallbacks = [];

    public static function getSeoFallbacks(): ?array
    {
        return config('seo-suite.fallbacks') ?? static::$seoFallbacks;
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }
}
