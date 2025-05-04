<?php

namespace DDR\LaravelQualityTools\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \DDR\LaravelQualityTools\LaravelQualityTools
 */
class LaravelQualityTools extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \DDR\LaravelQualityTools\LaravelQualityTools::class;
    }
}
