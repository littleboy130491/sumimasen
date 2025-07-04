<?php

namespace Littleboy130491\Sumimasen\Filament\Abstracts;

abstract class BaseContentResource extends BaseResource
{
    protected static function formRelationshipsFields(): array
    {
        return []; // relationships are handled in the child class
    }
}
