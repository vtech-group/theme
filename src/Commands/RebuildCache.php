<?php

namespace Vtech\Theme\Commands;

/**
 * The RemoveTheme class.
 *
 * @package vtech/theme
 *
 * @author  Jackie Do <anhvudo@gmail.com>
 */
class RebuildCache extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'theme:rebuild-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuild the themes cache file.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $this->themes->rebuildCache();

        $this->successBlock('Themes cache rebuilded.');
    }
}
