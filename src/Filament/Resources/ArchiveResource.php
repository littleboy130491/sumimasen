<?php

namespace Littleboy130491\Sumimasen\Filament\Resources;

use Littleboy130491\Sumimasen\Filament\Abstracts\BaseContentResource;
use Littleboy130491\Sumimasen\Filament\Resources\ArchiveResource\Pages;

class ArchiveResource extends BaseContentResource
{
    protected static ?string $model = null;

    public static function getModel(): string
    {
        return static::$model ??= class_exists(\App\Models\Archive::class)
            ? \App\Models\Archive::class
            : \Littleboy130491\Sumimasen\Models\Archive::class;
    }

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationGroup = 'Patterns';

    protected static ?int $navigationSort = 5;

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArchives::route('/'),
            'create' => Pages\CreateArchive::route('/create'),
            'edit' => Pages\EditArchive::route('/{record}/edit'),
        ];
    }
}
