<?php

namespace Littleboy130491\Sumimasen\Filament\Resources\ComponentResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Littleboy130491\Sumimasen\Filament\Resources\ComponentResource;

class ListComponents extends ListRecords
{
    protected static string $resource = ComponentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
