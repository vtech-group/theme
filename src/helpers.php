<?php

if (!function_exists('themes_path')) {
    /**
     * Get the fully qualified path to the directory used to
     * store Views of all themes.
     *
     * @param string $path The path to a given file or directory
     *                     within the theme path
     *
     * @return string
     */
    function themes_path($path = null)
    {
        return app('themes')->themesPath($path);
    }
}

if (!function_exists('theme_asset')) {
    /**
     * Generate a URL for an asset of theme using the current
     * scheme of the request.
     *
     * @param string    $path
     * @param bool|null $secure
     *
     * @return string
     */
    function theme_asset($path, $secure = null)
    {
        return app('themes')->asset($path, $secure);
    }
}

if (!function_exists('theme_css')) {
    /**
     * Generate a link tag for an asset of theme using the
     * current scheme of the request.
     *
     * @param string    $path
     * @param bool|null $secure
     *
     * @return string
     */
    function theme_css($path, $secure = null)
    {
        return app('themes')->css($path, $secure);
    }
}

if (!function_exists('theme_js')) {
    /**
     * Generate a script tag for an asset of theme using the
     * current scheme of the request.
     *
     * @param string    $path
     * @param bool|null $secure
     *
     * @return string
     */
    function theme_js($path, $secure = null)
    {
        return app('themes')->js($path, $secure);
    }
}

if (!function_exists('theme_img')) {
    /**
     * Generate a img tag for an asset of theme using the
     * current scheme of the request.
     *
     * @param string    $path
     * @param bool|null $secure
     * @param string    $alt
     * @param string    $Class
     *
     * @return string
     */
    function theme_img($path, $secure = null, $alt = '', $class = '', array $attributes = [])
    {
        return app('themes')->img($path, $secure, $alt, $class, $attributes);
    }
}
