<?php

namespace Vtech\Theme\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * The Theme facade.
 *
 * @package vtech/theme
 *
 * @author  Jackie Do <anhvudo@gmail.com>
 */
class Theme extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'themes';
    }
}
