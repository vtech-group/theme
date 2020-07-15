<?php

namespace Vtech\Theme\Traits;

use Vtech\Theme\Exceptions\InvalidThemeName;

/**
 * The ValidateThemeName trait.
 *
 * @package vtech/theme
 *
 * @author  Jackie Do <anhvudo@gmail.com>
 */
trait ValidateThemeName
{
    /**
     * Validate the name of theme.
     *
     * @param string $name The name of theme
     *
     * @throws InvalidThemeName
     *
     * @return bool
     */
    protected function validateThemeName($name)
    {
        // The characters are considered invalid naming directories: \/?%*:|"<>
        // Theme names are similar to directory names but accepts the slash character,
        // and are not allowed to start or end with a slash character.
        // The reason is that the theme name can be viewed as nested directories.
        $invalidChars = '\\\\?%*:|"<>';

        if (!preg_match('/^[^' . $invalidChars . ']+$/', $name)) {
            throw new InvalidThemeName($name, 'because contains one of the characters \?%*:|"<>');
        }

        if (!preg_match('/^[^\/]+.*[^\/]+$/', $name)) {
            throw new InvalidThemeName($name, 'because start or end with a slash character');
        }

        return true;
    }
}
