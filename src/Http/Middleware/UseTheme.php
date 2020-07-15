<?php

namespace Vtech\Theme\Http\Middleware;

use Closure;
use Vtech\Theme\Facades\Theme;

/**
 * The UseTheme middleware.
 *
 * @package vtech/theme
 *
 * @author  Jackie Do <anhvudo@gmail.com>
 */
class UseTheme
{
    /**
     * Handle an incoming request.
     *
     * @param Illuminate\Http\Request $request The request
     * @param string                  $name    The name of theme
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $name)
    {
        Theme::uses($name);

        return $next($request);
    }
}
