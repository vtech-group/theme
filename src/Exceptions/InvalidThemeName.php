<?php

namespace Vtech\Theme\Exceptions;

class InvalidThemeName extends ThemeException
{
    public function __construct($string, $reason = null)
    {
        $message = 'The string [' . $string . '] is not considered a valid theme name';
        $message .= $reason ? ' (' . $reason . ').' : '.';

        parent::__construct($message, 1);
    }
}
