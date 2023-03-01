<?php

namespace Vtech\Theme;

use BadMethodCallException;
use Illuminate\Support\Arr;
use Jackiedo\PathHelper\Path;
use Symfony\Component\Finder\Finder;
use Vtech\Theme\Exceptions\InvalidCacheFile;
use Vtech\Theme\Exceptions\InvalidThemeJsonFile;
use Vtech\Theme\Exceptions\ThemeAlreadyExists;
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
     * Store all valid themes have been initialized.
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

        throw new BadMethodCallException(sprintf('No theme is activated. Method %s::%s does not exist.', __CLASS__, $method));
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
     * remove the cache file.
     *
     * @return bool
     */
    public function clearCached()
    {
        return @unlink($this->getCachedPath());
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
            $themeName   = Path::normalize($themeFolder, '/');

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

        return Path::normalize($path);
    }

    /**
     * Get the path to cache file.
     *
     * @return string
     */
    public function getCachedPath()
    {
        return $this->cacheFile;
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
     * Check if theme is registered by its name.
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
     * Find a theme by its name.
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
        // Store all view paths of the previous theme and its parents
        $previousThemePaths = $this->activated() ? $this->current()->collectViewPaths() : [];

        // Get the new theme want to use
        $theme = $this->tryCall([$this, 'find'], $name);

        if (!$theme) {
            // Create anonymous theme that extends default theme
            $theme   = $this->initTheme(['name' => $name]);
            $default = config('themes.default');

            if ($default && $this->exists($default)) {
                $theme->setParent($this->find($default));
            }
        }

        // Active new theme
        $this->active = $theme;

        // Get all view paths of recently activated theme and its parent
        $recentThemePaths = $theme->collectViewPaths();

        // Add fallback theme paths using original paths (default view.paths config)
        // except theme paths of the previous theme
        $configViewPaths = config('view.paths');

        foreach ($configViewPaths as $path) {
            $path = Path::normalize($path);

            if (!in_array($path, $recentThemePaths) && !in_array($path, $previousThemePaths)) {
                $recentThemePaths[] = $path;
            }
        }

        // Set paths for view finder
        $viewFinder = app('view.finder');
        $viewFinder->setPaths($recentThemePaths);
        $viewFinder->flush();

        // Reconfigure view paths
        config(['view.paths' => $recentThemePaths]);

        // Fire event
        event('theme.change', $theme);

        return $theme;
    }

    /**
     * Check if any themes has been used.
     *
     * @return bool
     */
    public function activated()
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
        return $this->activated() ? $this->active->name : null;
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
        if (!$this->activated()) {
            return asset(Path::normalize($path, '/'), $secure);
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
            $this->htmlAttributes($attributes)
        );
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

        $this->themesPath = Path::normalize($path);

        return $this;
    }

    /**
     * Set the path to cache file.
     *
     * @return $this
     */
    protected function setCachePath()
    {
        $this->cacheFile = Path::normalize(base_path('bootstrap/cache/themes.php'));

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
    protected function htmlAttributes(array $attributes = [])
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
