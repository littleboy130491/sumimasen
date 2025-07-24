<?php

namespace Littleboy130491\Sumimasen\Filament\Abstracts;

use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Littleboy130491\Sumimasen\Filament\Builders\FormBuilder;
use Littleboy130491\Sumimasen\Filament\Builders\TableBuilder;
use Littleboy130491\Sumimasen\Filament\Traits\HasContentBlocks;
use Littleboy130491\Sumimasen\Filament\Traits\HasCopyFromDefaultLangButton;
use Littleboy130491\Sumimasen\Filament\Traits\ModelIntrospector;
use Littleboy130491\Sumimasen\Services\RecordDuplicator;

abstract class BaseResource extends Resource
{
    use HasContentBlocks,
        HasCopyFromDefaultLangButton,
        ModelIntrospector;

    protected static ?string $recordTitleAttribute = 'title';

    protected static function isTranslatable(): bool
    {
        return config('cms.multilanguage_enabled', false);
    }

    protected static function hiddenFields(): array
    {
        return []; // required fields (ex:'title' and 'slug') should never be hidden
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema(static::getFormBuilder()->buildSchema())
            ->columns(2);
    }

    protected static function getFormBuilder(): FormBuilder
    {
        return new FormBuilder(
            modelClass: static::$model,
            hiddenFields: static::hiddenFields(),
            resourceClass: new static
        );
    }

    public static function table(Table $table): Table
    {
        $tableBuilder = static::getTableBuilder();

        return $table
            ->columns($tableBuilder->buildColumns())
            ->filters($tableBuilder->buildFilters())
            ->actions($tableBuilder->buildActions())
            ->bulkActions($tableBuilder->buildBulkActions())
            ->headerActions($tableBuilder->buildHeaderActions())
            ->reorderable('menu_order')
            ->defaultSort('created_at', 'desc');
    }

    protected static function getTableBuilder(): TableBuilder
    {
        return new TableBuilder(
            modelClass: static::$model,
            hiddenFields: static::hiddenFields(),
            resourceClass: new static
        );
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    /**
     * Replicate actions for table records
     */
    protected static function duplicateRecord(Model $record): Model
    {
        $duplicator = new RecordDuplicator(
            modelClass: static::$model,
            relationshipsToReplicate: static::getRelationshipsToReplicate()
        );

        return $duplicator->duplicate($record);
    }

    /**
     * Relationships to replicate when duplicating records
     */
    protected static function getRelationshipsToReplicate(): array
    {
        return ['categories', 'tags']; // Default relationships
    }

    // Hook methods for extending functionality in child classes
    protected static function additionalTranslatableFormFields(?string $locale): array
    {
        return []; // hook for additional translatable fields
    }

    protected static function additionalNonTranslatableFormFields(): array
    {
        return []; // hook for additional non-translatable fields
    }

    protected static function formRelationshipsFields(): array
    {
        return []; // relationships are handled in the child class
    }

    protected static function additionalTableColumns(): array
    {
        return []; // hook for additional columns
    }

    protected static function tableExportBulkAction(): array
    {
        return [];
    }
}
