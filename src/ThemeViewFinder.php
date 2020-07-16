<?php

namespace Vtech\Theme;

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
     * Get the views that have been located.
     *
     * @return array
     */
    public function getViews()
    {
        return $this->views;
    }

    /**
     * Override the findNamespacedView() method to add theme paths to
     * namespace hint before finding.
     *
     * @param string $name
     *
     * @return string
     */
    protected function findNamespacedView($name)
    {
        list($namespace, $view) = $this->parseNamespaceSegments($name);

        $this->addThemePathsHint($namespace);

        return $this->findInPaths($view, $this->hints[$namespace]);
    }

    /**
     * Add theme paths to namespace hint.
     *
     * @param string $namespace
     *
     * @return void
     */
    protected function addThemePathsHint($namespace)
    {
        $themesApp  = app('themes');
        $themePaths = $themesApp->activated() ? $themesApp->current()->collectViewPaths() : [];
        $hints      = $this->hints[$namespace];

        if ('errors' == $namespace) {
            $additionals = array_map(function ($path) use ($namespace) {
                return unify_separator($path . '/' . $namespace);
            }, $themePaths);
        } else {
            $additionals = array_map(function ($path) use ($namespace) {
                return unify_separator($path . '/vendor/' . $namespace);
            }, $themePaths);
        }

        foreach ($hints as $path) {
            $path = unify_separator($path);

            if (!in_array($path, $additionals)) {
                $additionals[] = $path;
            }
        }

        $this->hints[$namespace] = $additionals;
    }
}
