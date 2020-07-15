<?php

namespace Vtech\Theme\Exceptions;

class AssetNotFound extends ThemeException
{
    public function __construct($assetPath, $name = null)
    {
        $message = 'The asset [' . $assetPath . '] not found';
        $message .= $name ? ' in the theme [' . $name . '].' : '.';

        parent::__construct($message, 1);
    }
}
