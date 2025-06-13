<?php

namespace Afatmustafa\SeoSuite\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Afatmustafa\SeoSuite\SeoSuite
 */
class SeoSuite extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Afatmustafa\SeoSuite\SeoSuite::class;
    }
}
