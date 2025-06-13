<?php

namespace Littleboy130491\Sumimasen\Filament\Resources\PageResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Littleboy130491\Sumimasen\Filament\Exports\PageExporter;
use Littleboy130491\Sumimasen\Filament\Imports\PageImporter;
use Littleboy130491\Sumimasen\Filament\Resources\PageResource;

class ListPages extends ListRecords
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ImportAction::make()
                ->importer(PageImporter::class),
            Actions\ExportAction::make()
                ->exporter(PageExporter::class),
            Actions\CreateAction::make(),
        ];
    }
}
