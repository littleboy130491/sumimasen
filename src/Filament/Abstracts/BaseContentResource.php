<?php

namespace Littleboy130491\Sumimasen\Filament\Abstracts;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;

abstract class BaseContentResource extends BaseResource
{
    protected static function formContentFields(): array
    {
        $fields = [];

        if (static::modelHasColumn('content')) {
            $fields[] = RichEditor::make('content')
                ->nullable();
        }

        if (static::modelHasColumn('excerpt')) {
            $fields[] = Textarea::make('excerpt')
                ->nullable();
        }

        return $fields;
    }

    protected static function formTemplateField(): array
    {
        if (! static::modelHasColumn('template')) {
            return [];
        }

        $subPath = '';

        return static::getTemplateOptions($subPath);
    }

    protected static function formRelationshipsFields(): array
    {
        return []; // relationships are handled in the child class
    }
}
