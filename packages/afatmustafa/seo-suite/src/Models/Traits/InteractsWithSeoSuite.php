<?php

namespace Afatmustafa\SeoSuite\Models\Traits;

use Afatmustafa\SeoSuite\Enums\OpenGraphTypes;
use Afatmustafa\SeoSuite\Enums\XCardTypes;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait InteractsWithSeoSuite
{
    public function seoSuite(): MorphOne
    {
        return $this->morphOne(config('seo-suite.model'), 'model');
    }

    public function getFallbackValue(string $column): string
    {
        /*
         *  First, we check if there is a Model-specific fallback.
         * If not, we pull fallbacks from the config.
         */
        $seoFallbacks = match (true) {
            property_exists($this, 'seoFallbacks') => $this->seoFallbacks,
            default => config('seo-suite.fallbacks')
        };

        /*
         * Next, we check if the column has a fallback.
         * If not, check config fallbacks for the column, otherwise return the column itself.
         */
        $fallbackColumn = match (true) {
            array_key_exists($column, $seoFallbacks) => $seoFallbacks[$column],
            array_key_exists($column, config('seo-suite.fallbacks')) => config('seo-suite.fallbacks')[$column],
            default => $column
        };

        return match ($column) {
            'title' => $this->$fallbackColumn,
            'description' => strip_tags($this->$fallbackColumn),
            'canonical_url' => request()->url(),
            'og_title' => $this->getSimpleSeoField('title'),
            'og_description' => $this->getSimpleSeoField('description'),
            'x_title' => $this->getSimpleSeoField('title'),
            'x_site' => config('app.url'),
            default => (property_exists($this, $fallbackColumn))
            ? strip_tags($this->$fallbackColumn)
            : ''
        };
    }

    public function getSimpleSeoField($field): string
    {
        return ($this->seoSuite->$field) ?
            $this->seoSuite->$field :
            $this->getFallbackValue($field);
    }

    public function getAdditionalMetaTags(): array
    {
        return $this->seoSuite->metas;
    }

    public function getOpenGraphType(): OpenGraphTypes
    {
        return match (true) {
            $this->seoSuite->og_type instanceof OpenGraphTypes => $this->seoSuite->og_type,
            default => OpenGraphTypes::ARTICLE
        };
    }

    public function getOpenGraphField($field): array
    {
        return match ($field) {
            'og_type_details' => $this->seoSuite->og_type_details ?? [],
            'og_properties' => $this->seoSuite->og_properties ?? [],
        };
    }

    public function getXCardType(): XCardTypes
    {
        return match (true) {
            $this->seoSuite->x_card_type instanceof XCardTypes => $this->seoSuite->x_card_type,
            default => XCardTypes::SUMMARY
        };
    }
}
