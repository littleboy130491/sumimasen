<?php

namespace Littleboy130491\Sumimasen\Filament\Resources\CommentResource\Pages;

use Littleboy130491\Sumimasen\Filament\Resources\CommentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditComment extends EditRecord
{
    protected static string $resource = CommentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
