<?php

namespace Littleboy130491\Sumimasen\Filament\Resources\PostResource\Pages;

use Littleboy130491\Sumimasen\Filament\Resources\PostResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
