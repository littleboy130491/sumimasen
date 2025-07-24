<?php

namespace Littleboy130491\Sumimasen\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Littleboy130491\Sumimasen\Enums\ContentStatus;

class RecordDuplicator
{
    public function __construct(
        protected string $modelClass,
        protected array $relationshipsToReplicate = ['categories', 'tags']
    ) {}

    public function duplicate(Model $record): Model
    {
        $newRecord = $record->replicate();

        $this->handleMultilingualSlugs($record, $newRecord);
        $this->resetDraftStatus($newRecord);

        $newRecord->save();

        $this->replicateRelationships($record, $newRecord);

        return $newRecord;
    }

    protected function handleMultilingualSlugs(Model $record, Model $newRecord): void
    {
        if (! method_exists($newRecord, 'getTranslations')) {
            return;
        }

        $originalSlugs = $newRecord->getTranslations('slug');
        $newSlugs = [];
        $locales = $this->getAvailableLocales();

        foreach ($locales as $locale) {
            $originalSlug = Arr::get($originalSlugs, $locale);
            $newSlugs[$locale] = $originalSlug ? $this->generateUniqueSlug($originalSlug, $locale) : null;
        }

        $newRecord->setTranslations('slug', $newSlugs);
    }

    protected function generateUniqueSlug(string $originalSlug, string $locale): string
    {
        $count = 1;
        $newSlug = $originalSlug;
        $modelClass = $this->modelClass;

        while ($modelClass::whereJsonContains("slug->{$locale}", $newSlug)->exists()) {
            $newSlug = "{$originalSlug}-copy-{$count}";
            $count++;
        }

        return $newSlug;
    }

    protected function resetDraftStatus(Model $newRecord): void
    {
        if ($this->hasAttribute($newRecord, 'status')) {
            $newRecord->status = ContentStatus::Draft;
        }

        if ($this->hasAttribute($newRecord, 'published_at')) {
            $newRecord->published_at = null;
        }
    }

    protected function replicateRelationships(Model $original, Model $replica): void
    {
        foreach ($this->relationshipsToReplicate as $relationshipName) {
            if ($this->modelHasRelationship($relationshipName)) {
                try {
                    $relationship = $original->{$relationshipName}();

                    if ($relationship instanceof \Illuminate\Database\Eloquent\Relations\BelongsToMany) {
                        $relatedIds = $original->{$relationshipName}()->pluck($relationship->getRelatedKeyName())->toArray();
                        if (! empty($relatedIds)) {
                            $replica->{$relationshipName}()->attach($relatedIds);
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning("Failed to replicate relationship '{$relationshipName}': ".$e->getMessage());
                }
            }
        }
    }

    protected function hasAttribute(Model $model, string $attribute): bool
    {
        return array_key_exists($attribute, $model->getAttributes()) || $model->isFillable($attribute);
    }

    protected function modelHasRelationship(string $relationship): bool
    {
        $modelClass = app($this->modelClass);

        if (! method_exists($modelClass, $relationship)) {
            return false;
        }

        try {
            $result = $modelClass->{$relationship}();

            return $result instanceof \Illuminate\Database\Eloquent\Relations\Relation;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function getAvailableLocales(): array
    {
        return array_keys(config('cms.language_available', []));
    }

    public function setRelationshipsToReplicate(array $relationships): self
    {
        $this->relationshipsToReplicate = $relationships;

        return $this;
    }
}
