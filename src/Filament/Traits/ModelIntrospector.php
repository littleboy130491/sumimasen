<?php

namespace Littleboy130491\Sumimasen\Filament\Traits;

use Illuminate\Database\Eloquent\Model;

trait ModelIntrospector
{
    protected static function isFieldHidden(string $field): bool
    {
        return in_array($field, static::hiddenFields());
    }

    protected static function modelHasColumn(string $column): bool
    {
        $modelClass = app(static::$model);

        return in_array($column, $modelClass->getFillable()) ||
            array_key_exists($column, $modelClass->getCasts()) ||
            $modelClass->hasAttribute($column);
    }

    protected static function modelHasRelationship(string $relationship): bool
    {
        $modelClass = app(static::$model);

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

    protected static function hasAttribute(Model $model, string $attribute): bool
    {
        return array_key_exists($attribute, $model->getAttributes()) || $model->isFillable($attribute);
    }

    protected static function getAvailableLocales(): array
    {
        return array_keys(config('cms.language_available'));
    }
}
