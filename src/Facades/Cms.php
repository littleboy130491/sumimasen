<?php

namespace Littleboy130491\Sumimasen\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Littleboy130491\Sumimasen\Cms
 */
class Cms extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Littleboy130491\Sumimasen\Cms::class;
    }
}
