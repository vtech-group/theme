<?php

namespace Vtech\Theme\Commands;

use Illuminate\Filesystem\Filesystem;
use Vtech\Console\Command;

/**
 * The Base Command.
 *
 * @package vtech/theme
 *
 * @author  Jackie Do <anhvudo@gmail.com>
 */
class BaseCommand extends Command
{
    /**
     * The file handler.
     *
     * @var Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The theme system.
     *
     * @var Vtech\Theme\ThemeSystem
     */
    protected $themes;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files  = $files;
        $this->themes = app('themes');
    }
}
