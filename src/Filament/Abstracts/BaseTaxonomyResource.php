<?php

namespace Littleboy130491\Sumimasen\Filament\Abstracts;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;

abstract class BaseTaxonomyResource extends BaseResource
{

    protected static function formTemplateField(string $subPath = 'archives'): array
    {
        if (!static::modelHasColumn('template')) {
            return [];
        }

        return static::getTemplateOptions($subPath);
    }
    

    protected static function tableBulkEditAction(): array
    {
        return []; // no bulk edit action for taxonomy resources
    }
}