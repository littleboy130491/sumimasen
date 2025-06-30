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
     * Get the Filament resource class for this commentable model.
     */
    public function getFilamentResourceClass(): ?string
    {
        $resourceMapping = static::getCommentableResourceMapping();
        return $resourceMapping[static::class] ?? static::resolveResourceFromModel(static::class);
    }

    /**
     * Get the edit URL for this commentable model in Filament.
     */
    public function getFilamentEditUrl(): ?string
    {
        $resourceClass = $this->getFilamentResourceClass();

        if (!$resourceClass || !class_exists($resourceClass)) {
            return null;
        }

        return $resourceClass::getUrl('edit', ['record' => $this]);
    }

    /**
     * Get the comments relation manager class following standard naming conventions.
     */
    public function getCommentsRelationManagerClass(): ?string
    {
        $resourceClass = $this->getFilamentResourceClass();

        if (!$resourceClass) {
            return null;
        }

        // Standard naming convention: {ResourceClass}\RelationManagers\CommentsRelationManager
        $relationManagerClass = $resourceClass . '\\RelationManagers\\CommentsRelationManager';

        return class_exists($relationManagerClass) ? $relationManagerClass : null;
    }

    /**
     * Get the mapping of commentable models to their Filament resources.
     */
    protected static function getCommentableResourceMapping(): array
    {
        return cache()->remember('commentable_resources_mapping', 3600, function () {
            return static::buildCommentableResourceMapping();
        });
    }

    /**
     * Build the mapping of commentable models to Filament resources.
     */
    protected static function buildCommentableResourceMapping(): array
    {
        $resources = [];

        // Get all registered Filament resources
        $filamentResources = collect(app('filament')->getResources());

        foreach ($filamentResources as $resourceClass) {
            $modelClass = $resourceClass::getModel();

            // Check if model uses HasComments trait
            if (static::modelHasCommentsTrait($modelClass)) {
                $resources[$modelClass] = $resourceClass;
            }
        }

        return $resources;
    }

    /**
     * Check if a model class uses the HasComments trait.
     */
    protected static function modelHasCommentsTrait(string $modelClass): bool
    {
        if (!class_exists($modelClass)) {
            return false;
        }

        $traits = class_uses_recursive($modelClass);

        return in_array(__TRAIT__, $traits);
    }

    /**
     * Resolve resource class from model using standard naming conventions.
     */
    protected static function resolveResourceFromModel(string $modelClass): ?string
    {
        // Extract model name from full class path
        $modelName = class_basename($modelClass);

        // Standard naming convention: replace \Models\ with \Filament\Resources\ and append Resource
        $expectedResourceClass = str_replace('\\Models\\', '\\Filament\\Resources\\', $modelClass) . 'Resource';

        // Check if the resource exists
        if (class_exists($expectedResourceClass)) {
            return $expectedResourceClass;
        }

        // Fallback: look in the same namespace as the package
        $packageNamespace = 'Littleboy130491\\Sumimasen\\Filament\\Resources\\';
        $packageResourceClass = $packageNamespace . $modelName . 'Resource';

        if (class_exists($packageResourceClass)) {
            return $packageResourceClass;
        }

        return null;
    }
}