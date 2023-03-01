<?php

namespace Vtech\Theme;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Vtech\Theme\Contracts\ThemeModel;
use Vtech\Theme\Exceptions\AssetNotFound;
use Vtech\Theme\Traits\Debug;
use Vtech\Theme\Traits\ValidateThemeName;

/**
 * The Theme class.
 *
 * @package vtech/theme
 *
 * @author  Jackie Do <anhvudo@gmail.com>
 */
class Theme implements ThemeModel
{
    use Debug;
    use ValidateThemeName;

    /**
     * The theme system.
     *
     * @var Vtech\Theme\ThemeSystem
     */
    protected $themes;

    /**
     * The theme attributes.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Create a new theme instance.
     *
     * @param array $attributes The theme attributes
     */
    final public function __construct(array $attributes = [])
    {
        $this->themes = app('themes');

        // Set name for theme
        $this->setName(Arr::pull($attributes, 'name'));

        // Supplement nessesary attributes
        $this->attributes['parent']    = null;
        $this->attributes['asset_url'] = null;

        // Set attributes from pre-defined
        $this->attributes = array_merge($this->attributes, $this->getPreDefinedAttributes());

        // Set attributes from input
        $this->setAttributes($attributes);
    }

    /**
     * Dynamic property accesstor.
     *
     * @param string $property The property
     *
     * @return mixed
     */
    final public function __get($property)
    {
        return $this->getAttribute($property);
    }

    /**
     * Get pre-defined theme's attributes.
     *
     * @return array
     */
    final public function getPreDefinedAttributes()
    {
        $attributes = [
            'human_name' => null,
            'version'    => null,
        ];

        if (property_exists($this, 'preDefined') && is_array($this->preDefined)) {
            $attributes = array_merge($attributes, $this->standardizeKeys($this->preDefined));

            return $this->filterGuarded($attributes);
        }

        return $attributes;
    }

    /**
     * Set theme's attributes.
     *
     * @return $this
     */
    final public function setAttributes(array $attributes = [])
    {
        $attributes = $this->standardizeKeys($attributes);
        $attributes = $this->filterGuarded($attributes);

        foreach ($attributes as $attribute => $value) {
            $setter = Str::camel('set_' . $attribute . '_attribute');

            if (method_exists($this, $setter)) {
                call_user_func_array([$this, $setter], [$value]);
            } else {
                $this->attributes[$attribute] = $value;
            }
        }

        return $this;
    }

    /**
     * Set value for a theme's attribute.
     *
     * @param string $attribute The attribute want to set
     * @param mixed  $value     The value of attribute
     *
     * @return $this
     */
    final public function setAttribute($attribute, $value)
    {
        if ($attribute) {
            $this->setAttributes([$attribute => $value]);
        }

        return $this;
    }

    /**
     * Get all theme's attributes.
     *
     * @return array
     */
    final public function getAttributes()
    {
        $output = [];

        foreach ($this->attributes as $attribute => $value) {
            $output[$attribute] = $this->getAttribute($attribute);
        }

        return $output;
    }

    /**
     * Get value of a theme's attribute.
     *
     * @param string $attribute The attribute want to get
     * @param mixed  $default   The value will be returned if attribute does not exist
     *
     * @return mixed
     */
    final public function getAttribute($attribute, $default = null)
    {
        $getter = Str::camel('get_' . $attribute . '_attribute');

        if (method_exists($this, $getter)) {
            return call_user_func_array([$this, $getter], []);
        }

        if (Arr::has($this->attributes, $attribute)) {
            return Arr::get($this->attributes, $attribute);
        }

        if ($parent = $this->parent) {
            return $parent->getAttribute($attribute, $default);
        }

        return $default;
    }

    /**
     * Get original value of a theme's attribute.
     *
     * @param string $attribute The theme's attribute
     * @param mixed  $default   The value will be returned if the attribute does not exist
     *
     * @return mixed
     */
    final public function getOriginal($attribute, $default = null)
    {
        if (Arr::has($this->attributes, $attribute)) {
            return Arr::get($this->attributes, $attribute);
        }

        return $default;
    }

    /**
     * Check if theme has specific attribute.
     *
     * @param string $attribute The attribute name
     *
     * @return bool
     */
    final public function hasAttribute($attribute)
    {
        if (Arr::has($this->attributes, $attribute)) {
            return true;
        }

        if (method_exists($this, Str::camel('get_' . $attribute . '_attribute'))) {
            return true;
        }

        return false;
    }

    /**
     * Check if a specific theme's view (do not include its parent) exists.
     *
     * @param string $name The view name
     *
     * @return bool
     */
    final public function hasView($name)
    {
        $viewFinder = app('view.finder');
        $extensions = $viewFinder->getExtensions();

        if (strpos($name, $viewFinder::HINT_PATH_DELIMITER) > 0) {
            $segments = explode($viewFinder::HINT_PATH_DELIMITER, $name);

            if (isset($segments[0])) {
                $segments[0] = 'vendor.' . $segments[0];
            }

            $name = implode('.', $segments);
        }

        foreach ($extensions as $extension) {
            if (file_exists($this->views_path . '/' . str_replace('.', '/', $name) . '.' . $extension)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a specific theme's asset (do not include its parent) exists.
     *
     * @param string $path The asset path
     *
     * @return bool
     */
    final public function hasAsset($path)
    {
        $assetsStorage = rtrim(config('themes.assets_folder'), '/\\');
        $assetsFolder  = $assetsStorage ? $assetsStorage . '/' . $this->name : $this->name;
        $assetFile     = $assetsFolder . '/' . $path;

        return is_file(public_path($assetFile));
    }

    /**
     * Extends a theme.
     *
     * @param ThemeModel $theme The theme wants to extend
     *
     * @return $this
     */
    final public function setParent(ThemeModel $theme)
    {
        if ($this->name !== $theme->name) {
            $this->attributes['parent'] = $theme;
        }

        return $this;
    }

    /**
     * Get all paths to directories used to store the views of
     * theme, inluding its parents.
     *
     * @return array Array of absolute paths
     */
    final public function collectViewPaths()
    {
        $paths = [];
        $theme = $this;

        do {
            $path = $theme->views_path;

            if (!in_array($path, $paths)) {
                $paths[] = $path;
            }
        } while ($theme = $theme->parent);

        return $paths;
    }

    /**
     * Generate a URL for an asset of theme.
     *
     * @param string    $path
     * @param bool|null $secure
     *
     * @return string
     */
    final public function asset($path, $secure = null)
    {
        // Return external URLs without modify
        if (preg_match('/^((http(s?):)?\/\/)/i', $path)) {
            return $path;
        }

        $path = ltrim(unify_separator($path, '/'), '/');

        // Is this theme use external asset url?
        if (!is_null($this->asset_url)) {
            return $this->asset_url . '/' . $path;
        }

        // Store original path to lookup in parent theme
        $originPath = $path;

        // Check for valid {xxx} keys and replace them with the Theme's attribute value
        preg_match_all('/\{(.*?)\}/', $path, $matches);

        foreach ($matches[1] as $attribute) {
            $value = $this->getAttribute($attribute);
            $path  = str_replace('{' . $attribute . '}', $value, $path);
        }

        // Seperate path from path queries
        if (false !== ($position = strpos($path, '?'))) {
            $baseUrl = substr($path, 0, $position);
            $params  = substr($path, $position);
        } else {
            $baseUrl = $path;
            $params  = '';
        }

        // Lookup asset in current's theme assets folder
        $assetsStorage = rtrim(config('themes.assets_folder'), '/\\');
        $assetsFolder  = $assetsStorage ? $assetsStorage . '/' . $this->name : $this->name;
        $fullUrl       = unify_separator($assetsFolder . '/' . $baseUrl, '/');

        if (file_exists(public_path($fullUrl))) {
            return asset($fullUrl . $params, $secure);
        }

        // If not found then lookup in parent's theme asset path
        if ($this->parent) {
            return $this->parent->asset($originPath, $secure);
        }

        // No parent theme? Lookup in the public folder.
        if (file_exists(public_path($baseUrl))) {
            return asset(unify_separator($path, '/'), $secure);
        }

        // Asset not found at all. Error handling
        return $this->tryCall(function ($assetPath, $themeName) {
            throw new AssetNotFound($assetPath, $themeName);
        }, [$baseUrl, $this->themes->name()], asset(unify_separator($path, '/'), $secure));
    }

    /**
     * Get the path to directory used to stores views of theme.
     *
     * @return string
     */
    final public function getViewsPathAttribute()
    {
        return unify_separator($this->themes->themesPath($this->name));
    }

    /**
     * Get the path to directory used to stores assets of theme.
     *
     * @return string
     */
    final public function getAssetsPathAttribute()
    {
        $assetStorage = config('themes.assets_folder') . '/' . $this->name;

        return unify_separator(public_path(ltrim($assetStorage, '/')));
    }

    /**
     * Get the theme's name.
     *
     * @return string
     */
    final public function getNameAttribute()
    {
        return $this->attributes['name'];
    }

    /**
     * Get the theme's human-readable name.
     *
     * @return string
     */
    final public function getHumanNameAttribute()
    {
        if (!$this->attributes['human_name']) {
            $name = last(explode('/', $this->attributes['name']));
            $name = str_replace(['-', '_'], ' ', $name);

            return Str::title($name);
        }

        return $this->attributes['human_name'];
    }

    /**
     * Set the theme's version.
     *
     * @param string $version The version of theme
     */
    final public function setVersionAttribute($version)
    {
        $version = trim($version);
        $version = ltrim($version, 'vV');

        if (preg_match('/^[-\w\.]+$/', $version)) {
            $this->attributes['version'] = $version;
        }

        return $this;
    }

    /**
     * Get the theme's version.
     *
     * @return string
     */
    final public function getVersionAttribute()
    {
        $version = $this->attributes['version'];

        return $version ?: '1.0.0';
    }

    /**
     * Get the theme's parent.
     *
     * @return Theme|null
     */
    final public function getParentAttribute()
    {
        return $this->attributes['parent'];
    }

    /**
     * Set external asset url attribute for theme.
     *
     * @param string $url
     *
     * @return $this
     */
    final public function setAssetUrlAttribute($url)
    {
        $url = trim($url);

        if (preg_match('/^(http(s?):)?\/\/.+/i', $url)) {
            $this->attributes['asset_url'] = rtrim(unify_separator($url, '/'), '/');
        }

        return $this;
    }

    /**
     * Get external asset url attribute of theme.
     *
     * @return string
     */
    final public function getAssetUrlAttribute()
    {
        return $this->attributes['asset_url'];
    }

    /**
     * Set the theme's name.
     *
     * @param string $name The name of theme
     *
     * @return $this
     */
    final protected function setName($name)
    {
        $this->validateThemeName($name);

        $this->attributes['name'] = $name;

        return $this;
    }

    /**
     * Standardize the attribute keys.
     *
     * @param array $attributes The attributes
     *
     * @return array
     */
    final protected function standardizeKeys(array $attributes = [])
    {
        $standardized = [];

        foreach ($attributes as $key => $value) {
            $key                = Str::snake($key);
            $standardized[$key] = $value;
        }

        return $standardized;
    }

    /**
     * Filter out the guarded attributes.
     *
     * @return array
     */
    final protected function filterGuarded(array $attributes = [])
    {
        return Arr::except($attributes, ['name', 'parent', 'extends']);
    }
}
