<?php

namespace Vtech\Theme\Exceptions;

class ThemeAlreadyExists extends ThemeException
{
    public function __construct($name)
    {
        parent::__construct('The theme [' . $name . '] already exists.', 1);
    }
}
