<?php

namespace Littleboy130491\Sumimasen\Filament\Abstracts;

use Filament\Forms\Components\RichEditor;

abstract class BaseTaxonomyResource extends BaseResource
{
    protected static function formContentFields(): array
    {
        if (! static::modelHasColumn('content')) {
            return [];
        }

        return [
            RichEditor::make('content')
                ->nullable(),
        ];
    }

    protected static function formCustomFields(): array
    {
        return [

        ]; // Custom fields are not applicable for taxonomy resources
    }

    protected static function formTemplateField(): array
    {
        if (! static::modelHasColumn('template')) {
            return [];
        }

        $subPath = 'archives';

        return static::getTemplateOptions($subPath);
    }

    protected static function formAuthorRelationshipField(): array
    {
        return []; // No author relationship for taxonomy resources
    }

    protected static function formStatusField(): array
    {
        return []; // No status field for taxonomy resources
    }

    protected static function formFeaturedField(): array
    {
        return []; // No featured field for taxonomy resources
    }

    protected static function formPublishedDateField(): array
    {

        return []; // No published at field for taxonomy resources
    }

    protected static function tableFeaturedColumn(): array
    {
        return []; // No featured column for taxonomy resources
    }

    protected static function tableStatusColumn(): array
    {
        return []; // No status column for taxonomy resources

    }

    protected static function tableAuthorColumn(): array
    {
        return []; // No author column for taxonomy resources

    }

    protected static function tablePublishedAtColumn(): array
    {
        return [];
    }

    protected static function tableBulkEditAction(): array
    {
        return []; // no bulk edit action for taxonomy resources
    }
}
