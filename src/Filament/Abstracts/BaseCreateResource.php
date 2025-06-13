<?php

namespace Littleboy130491\Sumimasen\Filament\Abstracts;

use Filament\Resources\Pages\CreateRecord;

abstract class BaseCreateResource extends CreateRecord
{
    protected function getHeaderActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->formId('form'),
        ];
    }
}
