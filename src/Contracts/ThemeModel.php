<?php

namespace Vtech\Theme\Contracts;

/**
 * The ThemeModel intarface.
 *
 * @package vtech/theme
 *
 * @author  Jackie Do <anhvudo@gmail.com>
 */
interface ThemeModel
{
    /**
     * Set value for a theme's attribute.
     *
     * @param string $attribute The attribute want to set
     * @param mixed  $value     The value of attribute
     *
     * @return $this
     */
    public function setAttribute($attribute, $value);

    /**
     * Get value of a theme's attribute.
     *
     * @param string $attribute The attribute want to get
     * @param mixed  $default   The value will be returned if attribute does not exist
     *
     * @return mixed
     */
    public function getAttribute($attribute, $default = null);

    /**
     * Get original value of a theme's attribute.
     *
     * @param string $attribute The theme's attribute
     * @param mixed  $default   The value will be returned if the attribute does not exist
     *
     * @return mixed
     */
    public function getOriginal($attribute, $default = null);

    /**
     * Check if theme has specific attribute.
     *
     * @param string $attribute The attribute name
     *
     * @return bool
     */
    public function hasAttribute($attribute);

    /**
     * Check if a specific theme's view (do not include its parent) exists.
     *
     * @param string $name The view name
     *
     * @return bool
     */
    public function hasView($name);

    /**
     * Check if a specific theme's asset (do not include its parent) exists.
     *
     * @param string $path The asset path
     *
     * @return bool
     */
    public function hasAsset($path);

    /**
     * Extends a theme.
     *
     * @param ThemeModel $theme The theme wants to extend
     *
     * @return $this
     */
    public function setParent(self $theme);

    /**
     * Generate a URL for an asset of theme.
     *
     * @param string    $path
     * @param bool|null $secure
     *
     * @return string
     */
    public function asset($path, $secure = null);

    /**
     * Get all paths to directories used to store the views of
     * theme, inluding its parents.
     *
     * @return array Array of absolute paths
     */
    public function collectViewPaths();
}
