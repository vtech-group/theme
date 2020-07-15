<?php

namespace Vtech\Theme;

use BadMethodCallException;
use Illuminate\Support\Arr;
use Symfony\Component\Finder\Finder;
use Vtech\Theme\Exceptions\InvalidCacheFile;
use Vtech\Theme\Exceptions\InvalidThemeJsonFile;
use Vtech\Theme\Exceptions\ThemeAlreadyExists;
use Vtech\Theme\Exceptions\ThemeException;
use Vtech\Theme\Exceptions\ThemeNotFound;
use Vtech\Theme\Traits\InitTheme;

/**
 * The ThemeSystem class.
 *
 * @package vtech/theme
 *
 * @author  Jackie Do <anhvudo@gmail.com>
 */
class ThemeSystem
{
    use InitTheme;

    /**
     * The original view.paths config of Laravel.
     *
     * @var array
     */
    protected $laravelViewPaths;

    /**
     * The path to directory used to store Views of all themes.
     *
     * @var string
     */
    protected $themesPath;

    /**
     * The path to cache file.
     *
     * @var string
     */
    protected $cacheFile;

    /**
     * The current theme instance.
     *
     * @var Vtech\Theme\Theme|null
     */
    protected $active;

    /**
     * Store all valid themes have been scanned.
     *
     * @var array
     */
    protected $registered = [];

    /**
     * Create a new theme system instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->storeLaravelViewPaths();
        $this->setThemesPath();
        $this->setCachePath();
    }

    /**
     * Action as a proxy to the current theme.
     * Map theme's functions to the ThemeSystem class (Decorator Pattern).
     *
     * @param string $method The method name
     * @param array  $args   The method arguments
     *
     * @throws BadMethodCallException
     * @throws ThemeException
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        if ($theme = $this->current()) {
            if (method_exists($theme, $method)) {
                return call_user_func_array([$theme, $method], $args);
            }

            throw new BadMethodCallException(sprintf('Method %s::%s does not exist.', get_class($theme), $method));
        }

        throw new ThemeException(sprintf('No theme is set. Can not excute the proxy %s::%s', __CLASS__, $method), 1);
    }

    /**
     * Scan all available themes and register them into container.
     *
     * @return $this
     */
    public function load()
    {
        $inheritances = [];
        $reservations = config('themes.reservations', []);

        // Load defined reservation themes in the configuration file
        foreach ($reservations as $name => $info) {
            // Is it an element with no values?
            if (is_string($info)) {
                $name = $info;
                $info = [];
            }

            $info['name'] = $name;

            // Create theme instance
            if (!$theme = $this->initTheme($info)) {
                continue;
            }

            // Register theme
            if (!$this->register($theme)) {
                continue;
            }

            // Has a parent theme name? Store it to resolve later.
            if (Arr::get($info, 'extends')) {
                $inheritances[] = [
                    'theme'       => $theme,
                    'parent_name' => $info['extends'],
                ];
            }
        }

        // Scan real themes in themes storage
        $scannedThemes = $this->cacheEnabled() ? $this->loadCache() : $this->scanThemeJsons();

        foreach ($scannedThemes as $info) {
            // Create theme instance
            if (!$theme = $this->initTheme($info)) {
                continue;
            }

            // Register theme
            if (!$this->register($theme)) {
                continue;
            }

            // Has a parent theme name? Store it to resolve later.
            if (Arr::get($info, 'extends')) {
                $inheritances[] = [
                    'theme'       => $theme,
                    'parent_name' => $info['extends'],
                ];
            }
        }

        // All themes are registered. Now we can assign the parents to the child-themes
        foreach ($inheritances as $inheritance) {
            $childTheme  = $inheritance['theme'];
            $parentTheme = $this->tryCall([$this, 'find'], $inheritance['parent_name']);

            if ($parentTheme) {
                $childTheme->setParent($parentTheme);
            }
        }

        return $this;
    }

    /**
     * Determine if cache enabled.
     *
     * @return bool
     */
    public function cacheEnabled()
    {
        return (bool) config('themes.cache', false);
    }

    /**
     * Loads themes from the cache.
     *
     * @return array
     */
    public function loadCache()
    {
        if (!file_exists($this->cacheFile)) {
            $this->rebuildCache();
        }

        $data = include $this->cacheFile;

        if (null === $data) {
            $this->tryCall(function ($path) {
                throw new InvalidCacheFile($path);
            }, $this->cacheFile);

            return [];
        }

        return $data;
    }

    /**
     * Rebuilds the cache file.
     *
     * @return $this
     */
    public function rebuildCache()
    {
        $data = $this->scanThemeJsons();

        file_put_contents($this->cacheFile, "<?php\n\nreturn " . var_export($data, true) . ";\n");

        return $this;
    }

    /**
     * Scans themes storage path for theme.json files and
     * returns an array of themes information.
     *
     * @return array
     */
    public function scanThemeJsons()
    {
        $themesStorage = $this->themesPath();

        if (!is_dir($themesStorage)) {
            return [];
        }

        $themeInfo   = [];
        $jsonFinder  = new Finder;
        $subLevel    = (int) config('themes.sub_folder_level', 1);
        $depthOfScan = $subLevel ? ('==' . $subLevel) : '==1';

        $jsonFinder->files()->name('theme.json')->in($themesStorage)->depth($depthOfScan);

        foreach ($jsonFinder as $themeJson) {
            $themeFolder = realpath($themeJson->getPath());
            $themeFolder = substr($themeFolder, strlen($this->themesPath()) + 1);
            $themeName   = unify_separator($themeFolder, '/');

            // If theme.json is not an empty file parse json values
            $themeJsonPath = $themeJson->getPathname();
            $jsonContent   = file_get_contents($themeJsonPath);

            if ('' !== $jsonContent) {
                $data = json_decode($jsonContent, true);

                if (null === $data) {
                    $this->tryCall(function ($path) {
                        throw new InvalidThemeJsonFile($path);
                    }, $themeJsonPath);

                    continue;
                }
            } else {
                $data = [];
            }

            // Override the theme name
            $data['name'] = $themeName;

            // Add to output
            $themeInfo[] = $data;
        }

        return $themeInfo;
    }

    /**
     * Get the fully qualified path to the directory used to
     * store Views of all themes.
     *
     * @param string $path The path to a given file or directory
     *                     within the theme path
     *
     * @return string
     */
    public function themesPath($path = null)
    {
        $path = ltrim($path, '/\\');
        $path = $path ? $this->themesPath . '/' . $path : $this->themesPath;

        return unify_separator($path);
    }

    /**
     * Return list of registered themes.
     *
     * @return array
     */
    public function all()
    {
        return $this->registered;
    }

    /**
     * Check if theme is registered by it's name.
     *
     * @param string $name The name of theme
     *
     * @return bool
     */
    public function exists($name)
    {
        $themes = $this->all();

        foreach ($themes as $theme) {
            if ($theme->name == $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find a theme by it's name.
     *
     * @param string $name The name of theme
     *
     * @return Theme
     */
    public function find($name)
    {
        $themes = $this->all();

        foreach ($themes as $theme) {
            if ($theme->name == $name) {
                return $theme;
            }
        }

        throw new ThemeNotFound($name);
    }

    /**
     * Enable theme by name and set view paths for theme.
     *
     * @param string $name The name of theme
     *
     * @return Theme
     */
    public function uses($name)
    {
        $theme = $this->tryCall([$this, 'find'], $name);

        if (!$theme) {
            // Create anonymous theme that extends default theme
            $theme   = $this->initTheme(['name' => $name]);
            $default = config('themes.default');

            if ($default && $this->exists($default)) {
                $theme->setParent($this->find($default));
            }
        }

        // Set active theme
        $this->active = $theme;

        // Get all views path of theme and it's parent
        $paths = $theme->getFindViewPaths();

        // Fall-back to default paths (default view.paths config)
        foreach ($this->laravelViewPaths as $path) {
            $path = unify_separator($path);

            if (!in_array($path, $paths)) {
                $paths[] = $path;
            }
        }

        // Re-config view paths
        config(['view.paths' => $paths]);

        // Set paths for view finder
        $viewFinder = app('view.finder');
        $viewFinder->setPaths($paths);
        $viewFinder->flush();

        // Fire event
        event('theme.change', $theme);

        return $theme;
    }

    /**
     * Check if the theme has been used.
     *
     * @return bool
     */
    public function used()
    {
        return $this->active instanceof Theme;
    }

    /**
     * Get current theme.
     *
     * @return Theme|null
     */
    public function current()
    {
        return $this->active;
    }

    /**
     * Get current theme's name.
     *
     * @return string|null
     */
    public function name()
    {
        return $this->used() ? $this->active->name : null;
    }

    /**
     * Original view paths defined in Laravel view.paths config.
     *
     * @return array
     */
    public function getLaravelViewPaths()
    {
        return array_map('unify_separator', $this->laravelViewPaths);
    }

    /**
     * Generate a URL for an asset of current theme.
     *
     * @param string    $path
     * @param bool|null $secure
     *
     * @return string
     */
    public function asset($path, $secure = null)
    {
        if (!$this->used()) {
            return asset(unify_separator($path, '/'), $secure);
        }

        return $this->current()->asset($path, $secure);
    }

    /**
     * Generate a link tag for an asset of theme using the
     * current scheme of the request.
     *
     * @param string    $path
     * @param bool|null $secure
     *
     * @return string
     */
    public function css($path, $secure = null)
    {
        return sprintf('<link type="text/css" rel="stylesheet" href="%s">', $this->asset($path, $secure));
    }

    /**
     * Generate a script tag for an asset of theme using the
     * current scheme of the request.
     *
     * @param string    $path
     * @param bool|null $secure
     *
     * @return string
     */
    public function js($path, $secure = null)
    {
        return sprintf('<script type="text/javascript" src="%s"></script>', $this->asset($path, $secure));
    }

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
    public function img($path, $secure = null, $alt = '', $class = '', array $attributes = [])
    {
        return sprintf('<img src="%s" alt="%s" class="%s" %s>',
            $this->asset($path, $secure),
            $alt,
            $class,
            $this->HtmlAttributes($attributes)
        );
    }

    /**
     * Store the original Laravel view.paths config.
     *
     * @return $this
     */
    protected function storeLaravelViewPaths()
    {
        $this->laravelViewPaths = config('view.paths');

        return $this;
    }

    /**
     * Set the path to directory used to store Views of all themes.
     *
     * @return $this
     */
    protected function setThemesPath()
    {
        $path = (config('themes.path') ?: config('view.paths')[0]);
        $path = rtrim($path, '/\\');

        $this->themesPath = unify_separator($path);

        return $this;
    }

    /**
     * Set the path to cache file.
     *
     * @return $this
     */
    protected function setCachePath()
    {
        $this->cacheFile = unify_separator(base_path('bootstrap/cache/themes.php'));

        return $this;
    }

    /**
     * Register a theme.
     *
     * @param Theme $theme The theme instance
     *
     * @return bool
     */
    protected function register(Theme $theme)
    {
        if ($this->exists($theme->name)) {
            return $this->tryCall(function ($themeName) {
                throw new ThemeAlreadyExists($themeName);
            }, $theme->name, false);
        }

        $this->registered[$theme->name] = $theme;

        return true;
    }

    /**
     * Return attributes in html format.
     *
     * @param array $attributes The attributes want to be formated
     *
     * @return string
     */
    protected function HtmlAttributes(array $attributes = [])
    {
        $formatted = join(' ', array_map(function ($key) use ($attributes) {
            if (is_bool($attributes[$key])) {
                return $attributes[$key] ? $key : '';
            }

            return $key . '="' . $attributes[$key] . '"';
        }, array_keys($attributes)));

        return $formatted;
    }
}
