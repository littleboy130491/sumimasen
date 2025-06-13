<?php

namespace Afatmustafa\SeoSuite\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;

trait Accessors
{
    //    protected function getFallbackValue(string $column): string
    //    {
    //        // get this->model class
    //        dd($this->model->seoFallbacks);
    //        $fallbackColumn = (array_key_exists($column, static::getSeoFallbacks())) ? static::getSeoFallbacks()[$column] : $column;
    //        return match ($fallbackColumn) {
    //            'title' => $this->model->$fallbackColumn,
    //            'description' => strip_tags($this->model->$fallbackColumn),
    //            /*'og_title' => $this->getGeneralSeoData('title'),
    //            'og_description' => $this->getGeneralSeoData('description'),
    //            'og_type' => config('advanced-seo-suite.fallbacks.og_type')->value,
    //            'canonical_url' => request()->url(),
    //            'x_card_type' => config('advanced-seo-suite.fallbacks.card_type')->value,
    //            */
    //            default => (property_exists($this->model, $fallbackColumnName))
    //                ? strip_tags($this->model->$fallbackColumnName)
    //                : null
    //        };
    //    }
    //
    //    protected function seoTitle(): Attribute
    //    {
    //        return Attribute::make(
    //            get: fn () => ($this->title) ? $this->title : $this->getFallbackValue('title'),
    //        );
    //    }
}
