<?php

namespace Littleboy130491\Sumimasen\Filament\Resources\SubmissionResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Littleboy130491\Sumimasen\Filament\Resources\SubmissionResource;

class EditSubmission extends EditRecord
{
    protected static string $resource = SubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
