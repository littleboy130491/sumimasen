<?php

namespace Afatmustafa\SeoSuite\Enums;

use Filament\Support\Contracts\HasLabel;

enum XCardTypes: string implements HasLabel
{
    case SUMMARY = 'summary';
    case SUMMARY_LARGE_IMAGE = 'summary_large_image';
    case APP = 'app';
    case PLAYER = 'player';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::SUMMARY => __('seo-suite::seo-suite.x.card_types.summary'),
            self::SUMMARY_LARGE_IMAGE => __('seo-suite::seo-suite.x.card_types.summary_large_image'),
            self::APP => __('seo-suite::seo-suite.x.card_types.app'),
            self::PLAYER => __('seo-suite::seo-suite.x.card_types.player'),
        };
    }
}
