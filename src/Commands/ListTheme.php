<?php

namespace Vtech\Theme\Commands;

/**
 * The ListTheme class.
 *
 * @package vtech/theme
 *
 * @author  Jackie Do <anhvudo@gmail.com>
 */
class ListTheme extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'theme:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all existing themes.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $tableHeader = ['Theme Name', 'Extends', 'Views Path', 'Assets Path'];
        $themes      = array_map(function ($theme) {
            $info = [
                'name'       => $theme->name,
                'extends'    => $theme->parent ? $theme->parent->name : '',
                'viewsPath'  => substr($theme->views_path, strlen(base_path()) + 1),
                'assetsPath' => $theme->asset_url ?: substr($theme->assets_path, strlen(base_path()) + 1),
            ];

            return $info;
        }, $this->themes->all());

        $countThemes = count($themes);

        if (0 == count($themes)) {
            $this->info('You don\'t have any themes.');

            return;
        }

        if (1 == $countThemes) {
            $this->info('You have one theme as follow:');
        } else {
            $this->info('You have ' . $countThemes . ' themes as follow:');
        }

        $this->newLine();
        $this->table($tableHeader, $themes);
        $this->newLine();
        $this->comment('Note: All paths is relative from ' . base_path());
    }
}
