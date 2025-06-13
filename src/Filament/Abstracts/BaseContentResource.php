<?php

namespace Littleboy130491\Sumimasen\Filament\Abstracts;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;

abstract class BaseContentResource extends BaseResource
{
    protected static function formContentFields(string $locale): array
    {

        return [
            RichEditor::make('content')
                ->nullable(),
            Textarea::make('excerpt')
                ->nullable(),
        ];
    }

    protected static function formTemplateField(): array
    {
        $subPath = '';

        return static::getTemplateOptions($subPath);
    }

    protected static function formRelationshipsFields(): array
    {
        return []; // relationships are handled in the child class
    }
}
