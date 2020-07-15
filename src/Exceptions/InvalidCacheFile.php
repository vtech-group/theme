<?php

namespace Vtech\Theme\Exceptions;

class InvalidCacheFile extends ThemeException
{
    public function __construct($path)
    {
        parent::__construct('The cache file at [' . $path . '] is invalid.', 1);
    }
}
