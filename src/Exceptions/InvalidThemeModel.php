<?php

namespace Vtech\Theme\Exceptions;

class InvalidThemeModel extends ThemeException
{
    public function __construct($class)
    {
        parent::__construct('The [' . $class . '] class is not considered a valid theme model.', 1);
    }
}
