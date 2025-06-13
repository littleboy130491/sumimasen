<?php

namespace Littleboy130491\Sumimasen\Filament\Resources\ComponentResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Littleboy130491\Sumimasen\Filament\Resources\ComponentResource;

class EditComponent extends EditRecord
{
    protected static string $resource = ComponentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
