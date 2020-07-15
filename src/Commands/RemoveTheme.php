<?php

namespace Vtech\Theme\Commands;

/**
 * The RemoveTheme class.
 *
 * @package vtech/theme
 *
 * @author  Jackie Do <anhvudo@gmail.com>
 */
class RemoveTheme extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'theme:remove';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove an existing theme.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $themes = array_map(function ($theme) {
            return $theme->name;
        }, $this->themes->all());

        if (0 == count($themes)) {
            $this->info('You don\'t have any themes.');

            return;
        }

        $name = $this->choice('Which theme to remove?', array_values($themes), (1 == count($themes) ? 0 : null), 3, false);

        if (!$this->themes->exists($name)) {
            $this->errorBlock('The theme [' . $name . '] does not exist.');

            return false;
        }

        $theme        = $this->themes->find($name);
        $assetUrl     = $theme->asset_url;
        $removeAssets = false;

        if (empty($assetUrl)) {
            $removeAssets = $this->confirm('Do you want to remove all theme\'s assets', true);
        }

        $themeFolder = str_replace('.', '/', $name);
        $assetFolder = config('themes.assets_folder') . '/' . $themeFolder;

        $viewsPath  = $this->themes->themesPath($themeFolder);
        $assetsPath = unify_separator(public_path(ltrim($assetFolder, '/')));

        $this->block('The following resources will be deleted:');

        $summary = [
            'Views folder' => $viewsPath,
        ];

        if ($removeAssets) {
            $summary['Assets folder'] = $assetsPath;
        }

        $this->writeList($summary);

        if (!$this->confirm('Do you want to continue?')) {
            $this->warningBlock('Your job is cancelled.');

            return false;
        }

        $this->line('Removing theme...');
        $this->files->deleteDirectory($viewsPath);

        if ($removeAssets) {
            $this->files->deleteDirectory($assetsPath);
        }

        if ($this->themes->cacheEnabled()) {
            $this->themes->rebuildCache();
        }

        $this->successBlock('Your theme has been removed successfully.');
    }
}
