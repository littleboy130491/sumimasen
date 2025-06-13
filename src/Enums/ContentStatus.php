<?php

namespace Littleboy130491\Sumimasen\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ContentStatus: string implements HasLabel, HasColor
{
    case Draft = 'draft';
    case Published = 'published';
    case Scheduled = 'scheduled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
            self::Scheduled => 'Scheduled',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => Color::Yellow,
            self::Published => Color::Green,
            self::Scheduled => Color::Blue,
        };
    }
}