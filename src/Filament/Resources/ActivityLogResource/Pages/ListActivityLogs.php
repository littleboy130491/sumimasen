<?php

namespace Littleboy130491\Sumimasen\Filament\Resources\ActivityLogResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Littleboy130491\Sumimasen\Filament\Resources\ActivityLogResource;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Add clear logs action if needed
        ];
    }
}