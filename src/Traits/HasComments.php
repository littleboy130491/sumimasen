<?php

namespace Littleboy130491\Sumimasen\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Littleboy130491\Sumimasen\Models\Comment;

trait HasComments
{
    /**
     * Define the comments relationship.
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Get approved comments only.
     */
    public function approvedComments(): MorphMany
    {
        return $this->comments()->where('status', 'approved');
    }

    /**
     * Get the Filament resource class for this model (if it exists).
     */
    public function getFilamentResourceClass(): ?string
    {
        $modelName = class_basename(static::class);

        // Try standard Laravel naming convention first
        $expectedResourceClass = str_replace('\\Models\\', '\\Filament\\Resources\\', static::class) . 'Resource';

        if (class_exists($expectedResourceClass)) {
            return $expectedResourceClass;
        }

        // Try App namespace
        $appResourceClass = "App\\Filament\\Resources\\{$modelName}Resource";

        if (class_exists($appResourceClass)) {
            return $appResourceClass;
        }

        return null;
    }

    /**
     * Get the Filament edit URL for this model.
     */
    public function getFilamentEditUrl(): ?string
    {
        try {
            $resourceClass = $this->getFilamentResourceClass();

            if (!$resourceClass) {
                return null;
            }

            return $resourceClass::getUrl('edit', ['record' => $this]);
        } catch (\Exception $e) {
            return null;
        }
    }
}