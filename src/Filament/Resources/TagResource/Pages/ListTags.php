<?php

namespace Littleboy130491\Sumimasen\Filament\Resources\TagResource\Pages;

use Littleboy130491\Sumimasen\Filament\Resources\TagResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTags extends ListRecords
{
    protected static string $resource = TagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
