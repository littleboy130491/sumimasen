<?php

namespace Afatmustafa\SeoSuite\Enums;

use Filament\Support\Contracts\HasLabel;

enum MetaTypes: string implements HasLabel
{
    case NAME = 'name';
    case PROPERTY = 'property';
    case HTTP_EQUIV = 'http-equiv';
    case CHARSET = 'charset';
    case ITEM_PROP = 'itemprop';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::NAME => __('seo-suite::seo-suite.advanced.metas.meta_types.name'),
            self::PROPERTY => __('seo-suite::seo-suite.advanced.metas.meta_types.property'),
            self::HTTP_EQUIV => __('seo-suite::seo-suite.advanced.metas.meta_types.http_equiv'),
            self::CHARSET => __('seo-suite::seo-suite.advanced.metas.meta_types.charset'),
            self::ITEM_PROP => __('seo-suite::seo-suite.advanced.metas.meta_types.item_prop'),
        };
    }
}
