<?php

namespace Littleboy130491\Sumimasen\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, SoftDeletes;



    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'commentable_id',
        'commentable_type',
        'content',
        'email',
        'name',
        'parent_id',
        'status'
    ];


    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => Littleboy130491\Sumimasen\Enums\CommentStatus::class,
        'parent_id' => 'integer',
        'commentable_id' => 'integer'
    ];




    //--------------------------------------------------------------------------
    // Relationships
    //--------------------------------------------------------------------------

    /**
     * Define the commentable relationship.
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Define the parent relationship.
     */
    public function parent(): BelongsTo
    {
        // Use the base class name for the ::class constant
        // Add foreign key argument if specified in YAML
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Define the children relationship.
     */
    public function children(): HasMany
    {
        // Use the base class name for the ::class constant
        // Add foreign key argument if specified in YAML
        return $this->hasMany(Comment::class, 'parent_id');
    }

    /**
     * Get all children recursively
     *
     * @return Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }
}
