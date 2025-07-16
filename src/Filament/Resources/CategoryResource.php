<?php

namespace Littleboy130491\Sumimasen\Filament\Resources;

use Littleboy130491\Sumimasen\Filament\Abstracts\BaseTaxonomyResource;
use Littleboy130491\Sumimasen\Filament\Resources\CategoryResource\Pages;

class CategoryResource extends BaseTaxonomyResource
{
    protected static ?string $model = null;

    public static function getModel(): string
    {
        return static::$model ??= class_exists(\App\Models\Category::class)
            ? \App\Models\Category::class
            : \Littleboy130491\Sumimasen\Models\Category::class;
    }

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Contents';

    protected static ?int $navigationSort = 30;

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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
