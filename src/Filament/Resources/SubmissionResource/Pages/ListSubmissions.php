<?php

namespace Littleboy130491\Sumimasen\Filament\Resources\SubmissionResource\Pages;

use Littleboy130491\Sumimasen\Filament\Resources\SubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubmissions extends ListRecords
{
    protected static string $resource = SubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}


