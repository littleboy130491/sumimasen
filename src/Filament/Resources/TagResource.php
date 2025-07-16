<?php

namespace Littleboy130491\Sumimasen\Filament\Resources;

use Littleboy130491\Sumimasen\Filament\Abstracts\BaseTaxonomyResource;
use Littleboy130491\Sumimasen\Filament\Resources\TagResource\Pages;

class TagResource extends BaseTaxonomyResource
{
    protected static ?string $model = null;

    public static function getModel(): string
    {
        return static::$model ??= class_exists(\App\Models\Tag::class)
            ? \App\Models\Tag::class
            : \Littleboy130491\Sumimasen\Models\Tag::class;
    }

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Contents';

    protected static ?int $navigationSort = 40;

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTags::route('/'),
            'create' => Pages\CreateTag::route('/create'),
            'edit' => Pages\EditTag::route('/{record}/edit'),
        ];
    }
}
