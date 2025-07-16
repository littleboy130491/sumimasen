<?php

namespace Littleboy130491\Sumimasen\Filament\Resources;

use Littleboy130491\Sumimasen\Filament\Abstracts\BaseContentResource;
use Littleboy130491\Sumimasen\Filament\Resources\PageResource\Pages;

class PageResource extends BaseContentResource
{
    protected static ?string $model = null;

    public static function getModel(): string
    {
        return static::$model ??= class_exists(\App\Models\Page::class)
            ? \App\Models\Page::class
            : \Littleboy130491\Sumimasen\Models\Page::class;
    }

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Contents';

    protected static ?int $navigationSort = 0;

    protected static function formRelationshipsFields(): array
    {
        return [
            ...static::formParentRelationshipField(),
        ];
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
