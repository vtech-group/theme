<?php

namespace Vtech\Theme\Exceptions;

class ThemeNotFound extends ThemeException
{
    public function __construct($name)
    {
        parent::__construct('The theme [' . $name . '] not found.', 1);
    }
}
