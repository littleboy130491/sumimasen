<?php

namespace Littleboy130491\Sumimasen\Filament\Resources;

use Littleboy130491\Sumimasen\Filament\Resources\TagResource\Pages;
use Littleboy130491\Sumimasen\Models\Tag;
use Littleboy130491\Sumimasen\Filament\Abstracts\BaseTaxonomyResource;

class TagResource extends BaseTaxonomyResource
{
    protected static ?string $model = Tag::class;

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
