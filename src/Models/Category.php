<?php

namespace Littleboy130491\Sumimasen\Models;

use Afatmustafa\SeoSuite\Models\Traits\InteractsWithSeoSuite;
use Awcodes\Curator\Models\Media;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Category extends Model
{
    use HasFactory, HasTranslations, InteractsWithSeoSuite, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'content',
        'featured_image',
        'menu_order',
        'parent_id',
        'slug',
        'template',
        'title',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'parent_id' => 'integer',
        'menu_order' => 'integer',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array<int, string>
     */
    public $translatable = [
        'content',
        'slug',
        'title',
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

    /**
     * Define the posts relationship.
     */
    public function posts(): BelongsToMany
    {
        // Use the base class name for the ::class constant
        // Add foreign key argument if specified in YAML
        return $this->belongsToMany(Post::class);
    }

    /**
     * Define the parent relationship.
     */
    public function parent(): BelongsTo
    {
        // Use the base class name for the ::class constant
        // Add foreign key argument if specified in YAML
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Define the children relationship.
     */
    public function children(): HasMany
    {
        // Use the base class name for the ::class constant
        // Add foreign key argument if specified in YAML
        return $this->hasMany(Category::class, 'parent_id');
    }
}
