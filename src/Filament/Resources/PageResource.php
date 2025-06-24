<?php

namespace Littleboy130491\Sumimasen\Filament\Resources;

use Filament\Forms\Components\Builder as FormsBuilder;
use Littleboy130491\Sumimasen\Filament\Abstracts\BaseContentResource;
use Littleboy130491\Sumimasen\Filament\Resources\PageResource\Pages;
use Littleboy130491\Sumimasen\Filament\Traits\HasContentBlocks;
use Littleboy130491\Sumimasen\Models\Page;

class PageResource extends BaseContentResource
{
    use HasContentBlocks;

    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Contents';

    protected static ?int $navigationSort = 0;

    protected static function formSectionField(): array
    {
        if (! static::modelHasColumn('section')) {
            return [];
        }

        return [
            FormsBuilder::make('section')
                ->collapsed(false)
                ->blocks(static::getContentBlocks()),
        ];
    }

    protected static function formRelationshipsFields(): array
    {
        return [
            ...static::formParentRelationshipField(),
        ];
    }

    protected static function formFeaturedField(): array
    {
        return [];
    }

    protected static function tableFeaturedColumn(): array
    {
        return [];
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
