<?php

namespace Littleboy130491\Sumimasen\Filament\Resources;

use Littleboy130491\Sumimasen\Filament\Abstracts\BaseContentResource;
use Littleboy130491\Sumimasen\Filament\RelationManagers\CommentsRelationManager;
use Littleboy130491\Sumimasen\Filament\Resources\PostResource\Pages;

class PostResource extends BaseContentResource
{
    protected static ?string $model = null;

    public static function getModel(): string
    {
        return static::$model ??= class_exists(\App\Models\Post::class)
            ? \App\Models\Post::class
            : \Littleboy130491\Sumimasen\Models\Post::class;
    }

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Contents';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'title';

    protected static function formRelationshipsFields(): array
    {
        return [
            ...static::formTaxonomyRelationshipField('categories'),
            ...static::formTaxonomyRelationshipField('tags'),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            CommentsRelationManager::class,
        ];
    }
}
