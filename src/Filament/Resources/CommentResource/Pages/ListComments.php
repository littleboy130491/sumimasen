<?php

namespace Littleboy130491\Sumimasen\Filament\Resources\CommentResource\Pages;

use Littleboy130491\Sumimasen\Filament\Resources\CommentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListComments extends ListRecords
{
    protected static string $resource = CommentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
