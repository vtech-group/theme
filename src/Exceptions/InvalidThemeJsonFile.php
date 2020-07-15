<?php

namespace Vtech\Theme\Exceptions;

class InvalidThemeJsonFile extends ThemeException
{
    public function __construct($path)
    {
        parent::__construct('The theme.json file at [' . $path . '] is invalid structure.', 1);
    }
}
