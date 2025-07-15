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
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => \Littleboy130491\Sumimasen\Enums\CommentStatus::class,
        'parent_id' => 'integer',
        'commentable_id' => 'integer',
    ];

    // --------------------------------------------------------------------------
    // Relationships
    // --------------------------------------------------------------------------

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
     * Recursively eager-load children comments.
     *
     * This relationship allows you to retrieve the comment tree while keeping
     * the hierarchical structure intact (each child comment will contain its
     * own `childrenRecursive` relation).
     */
    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }

    // ----------------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------------

    /**
     * Retrieve *all* descendant comments (flattened collection).
     *
     * Example:
     * ┌─ c1
     * │  ├─ c2
     * │  │  ├─ c6
     * │  │  ├─ c7
     * │  │  │  ├─ c9
     * │  │  │  └─ c10
     * │  │  └─ c8
     * │  ├─ c3
     * │  ├─ c4
     * │  └─ c5
     *
     * `$comment->descendants()` will return a collection containing
     * [c2,c6,c7,c9,c10,c8,c3,c4,c5] (order depends on the internal traversal).
     */
    public function descendants(): \Illuminate\Support\Collection
    {
        // Lazily load children to avoid the N+1 problem when possible.
        $children = $this->children()->with('children')->get();

        return $children->flatMap(function (self $child) {
            return collect([$child])->merge($child->descendants());
        });
    }

    /**
     * Scope a query to order comments by their `created_at` timestamp.
     *
     * Usage examples:
     * Comment::orderByDate()->get();                // newest first
     * Comment::orderByDate('asc')->get();           // oldest first
     * $post->comments()->orderByDate()->get();      // on relations
     */
    public function scopeOrderByDate($query, string $direction = 'desc')
    {
        return $query->orderBy('created_at', $direction);
    }
}
