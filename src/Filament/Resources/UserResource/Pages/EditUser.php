<?php

namespace Littleboy130491\Sumimasen\Filament\Resources\UserResource\Pages;

use Littleboy130491\Sumimasen\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
