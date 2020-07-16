<?php

namespace Vtech\Theme\Commands;

/**
 * The RemoveTheme class.
 *
 * @package vtech/theme
 *
 * @author  Jackie Do <anhvudo@gmail.com>
 */
class ClearCache extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'theme:clear-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove the themes cache file.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $this->themes->clearCached();

        $this->successBlock('Themes cache cleared.');
    }
}
