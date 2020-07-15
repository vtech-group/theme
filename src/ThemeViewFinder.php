<?php

namespace Vtech\Theme;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\View\FileViewFinder;

/**
 * The ThemeViewFinder class.
 *
 * @package vtech/theme
 *
 * @author  Jackie Do <anhvudo@gmail.com>
 */
class ThemeViewFinder extends FileViewFinder
{
    /**
     * The theme system.
     *
     * @var Vtech\Theme\ThemeSystem
     */
    protected $themes;

    /**
     * Create a new file view loader instance.
     *
     * @param Illuminate\Filesystem\Filesystem $files
     *
     * @return void
     */
    public function __construct(Filesystem $files, array $paths, array $extensions = null)
    {
        $this->themes = app('themes');

        parent::__construct($files, $paths, $extensions);
    }

    /**
     * Remap namespace paths.
     *
     * This method will remap all paths starting with "resources/views/vendor/..."
     * (relative from base_path()) to starting with "theme-name/vendor/..."
     * (relative from current theme path)
     *
     * @param string $namespace
     *
     * @return array
     */
    public function remapNamespacePaths($namespace)
    {
        // If the namespace does not exist
        if (!isset($this->hints[$namespace])) {
            return [];
        }

        // Define the string to search and replace
        $strSearch   = unify_separator('resources/views/vendor');
        $replacement = 'vendor';

        // Store the replaced strings
        $replaced = [];

        // Get the paths registered to the $namespace
        // and unify the separator before searching
        $paths = array_map('unify_separator', $this->hints[$namespace]);

        foreach ($paths as $path) {
            // Get the relative path from base path of project
            // and find the $strSearch string
            $relativeFromBase = substr($path, strlen(base_path()) + 1);
            $strSearchPos     = strpos($relativeFromBase, $strSearch);

            if (0 === $strSearchPos) {
                // Do replace
                $relativeFromBase = str_replace($strSearch, $replacement, $relativeFromBase);
                $replaced[]       = $relativeFromBase;
            }
        }

        // Prepend current theme's view paths to the remaped paths
        $additionals = $this->getAdditionalPaths();
        $newPaths    = [];

        foreach ($additionals as $path1) {
            foreach ($replaced as $path2) {
                $newPaths[] = $path1 . '/' . $path2;
            }
        }

        // Add new paths in the beggin of the search paths array
        foreach (array_reverse($newPaths) as $path) {
            if (!in_array($path, $paths)) {
                $paths = Arr::prepend($paths, unify_separator($path));
            }
        }

        return $paths;
    }

    /**
     * Override the replaceNamespace() method to add path for custom error views
     * ("theme-name/errors/...").
     *
     * @param string       $namespace
     * @param string|array $hints
     *
     * @return void
     */
    public function replaceNamespace($namespace, $hints)
    {
        $hints = (array) $hints;

        // Overide Error views and Mail Markdown views
        if ('errors' == $namespace) {
            $additionals = $this->getAdditionalPaths();

            $addPaths = array_map(function ($path) use ($namespace) {
                return unify_separator($path . '/' . $namespace);
            }, $additionals);

            foreach ($hints as $path) {
                $path = unify_separator($path);

                if (!in_array($path, $addPaths)) {
                    $addPaths[] = $path;
                }
            }

            $this->hints[$namespace] = $addPaths;
        } else {
            $this->hints[$namespace] = $hints;
        }
    }

    /**
     * Set the active view paths.
     *
     * @param array $paths
     *
     * @return $this
     */
    public function setPaths($paths)
    {
        $this->paths = $paths;

        return $this;
    }

    /**
     * Get the active view paths.
     *
     * @return array
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     * Get the views that have been located.
     *
     * @return array
     */
    public function getViews()
    {
        return $this->views;
    }

    /**
     * Override the findNamespacedView() method to add the "theme-name/vendor/..." paths.
     *
     * @param string $name
     *
     * @return string
     */
    protected function findNamespacedView($name)
    {
        // Extract the $view and the $namespace parts
        list($namespace, $view) = $this->parseNamespaceSegments($name);

        // Prepare theme paths
        $paths = $this->remapNamespacePaths($namespace);

        // Find and return the view
        return $this->findInPaths($view, $paths);
    }

    /**
     * Get the additional paths from the original Laravel view paths configuration.
     *
     * @return array
     */
    protected function getAdditionalPaths()
    {
        return array_diff($this->paths, $this->themes->getLaravelViewPaths());
    }
}
