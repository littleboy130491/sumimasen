<?php

namespace Littleboy130491\Sumimasen\Filament\Resources\CategoryResource\Pages;

use Littleboy130491\Sumimasen\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
