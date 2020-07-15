<?php

namespace Vtech\Theme\Commands;

use Exception;
use Illuminate\Support\Arr;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Vtech\Theme\Traits\InitTheme;

/**
 * The NewTheme class.
 *
 * @package vtech/theme
 *
 * @author  Jackie Do <anhvudo@gmail.com>
 */
class NewTheme extends BaseCommand
{
    use InitTheme;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'theme:new';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new theme.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        try {
            $name  = $this->argument('name');
            $theme = $this->initTheme(['name' => $name]);
        } catch (Exception $exception) {
            $this->errorBlock($exception->getMessage());

            return false;
        }

        if (!$theme) {
            $this->errorBlock('The theme name seems to be invalid. Please enable debug configuration for more details.');

            return false;
        }

        // Check for the existence of theme
        if ($this->themes->exists($name)) {
            $this->errorBlock('The theme [' . $name . '] already exists.');

            return false;
        }

        // Check for the existence of folders
        $viewsPath   = $this->themes->themesPath($name);
        $assetFolder = config('themes.assets_folder') . '/' . $name;
        $assetsPath  = public_path(ltrim($assetFolder, '/'));

        if ($this->files->exists($viewsPath)) {
            $this->errorBlock('Folder already exists: ' . $viewsPath);

            return false;
        }

        // Ask for parent theme
        $parentName = null;

        if ($this->confirm('Extends from another theme?')) {
            $themes = array_map(function ($theme) {
                return $theme->name;
            }, $this->themes->all());

            $parentName = $this->choice('Which one to extends?', array_values($themes), (1 == count($themes) ? 0 : null), 3, false);
        }

        // Ask for external asset url
        $assetUrl = null;

        if ($this->confirm('Use external url for assets?')) {
            $assetUrl = $this->ask('What is external url?', null, function ($answer) {
                if (false === filter_var($answer, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED)) {
                    throw new Exception('Invalid url', 1);
                }

                return rtrim($answer, '/');
            });
        }

        // Ask other information
        $otherInformation     = [];
        $preDefinedAttributes = Arr::except($theme->getPreDefinedAttributes(), ['asset_url']);

        foreach ($preDefinedAttributes as $key => $value) {
            if (is_string($value) || is_null($value) || is_numeric($value)) {
                $answer                 = $this->ask('What is ' . str_replace('_', ' ', $key) . '?', $theme->{$key});
                $otherInformation[$key] = ('' !== $answer) ? $answer : null;
                continue;
            }

            if (is_bool($value)) {
                $otherInformation[$key] = $this->confirm('How about ' . str_replace('_', ' ', $key) . '?', $theme->{$key});
                continue;
            }
        }

        // Display a summary and ask confirm
        $this->block('Please review the provided information:');

        $summary = [
            'Theme name' => $name,
            'Extends'    => $parentName,
            'Asset URL'  => $assetUrl,
        ];

        foreach ($otherInformation as $key => $value) {
            $key = ucfirst(str_replace('_', ' ', $key));

            if (is_string($value) || is_null($value) || is_numeric($value)) {
                $summary[$key] = $value;
                continue;
            }

            if (is_bool($value)) {
                $summary[$key] = $value ? 'true' : 'false';
                continue;
            }
        }

        $this->writeList($summary);

        if (!$this->confirm('Is that right?', true)) {
            $this->warningBlock('Your job is cancelled.');

            return false;
        }

        $this->line('Creating theme...');

        $themeJson = json_encode(array_merge([
            'extends'   => $parentName,
            'asset_url' => $assetUrl,
        ], $otherInformation), JSON_PRETTY_PRINT);

        $this->files->makeDirectory($viewsPath, 0777, true);
        $this->files->put($viewsPath . '/theme.json', $themeJson);

        if (!$assetUrl) {
            if (!$this->files->isDirectory($assetsPath)) {
                $this->files->makeDirectory($assetsPath, 0777, true);
            }
        }

        if ($this->themes->cacheEnabled()) {
            $this->themes->rebuildCache();
        }

        $this->successBlock('Your theme has been created successfully.');
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of theme.'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [];
    }
}
