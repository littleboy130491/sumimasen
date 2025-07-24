<?php

namespace Littleboy130491\Sumimasen\Models;

use Awcodes\Curator\Models\Media;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Littleboy130491\SeoSuite\Models\Traits\InteractsWithSeoSuite;
use Littleboy130491\Sumimasen\Traits\HasSections;
use Spatie\Translatable\HasTranslations;

class Archive extends Model
{
    use HasFactory, HasSections, HasTranslations, InteractsWithSeoSuite, SoftDeletes;

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
