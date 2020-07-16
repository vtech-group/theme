<?php

namespace Vtech\Theme;

use Illuminate\Support\ServiceProvider;
use Vtech\Theme\Commands\ClearCache;
use Vtech\Theme\Commands\ListTheme;
use Vtech\Theme\Commands\NewTheme;
use Vtech\Theme\Commands\RebuildCache;
use Vtech\Theme\Commands\RemoveTheme;

/**
 * The Service Provider.
 *
 * @package vtech/theme
 *
 * @author  Jackie Do <anhvudo@gmail.com>
 */
class ThemeServiceProvider extends ServiceProvider
{
    /**
     * The package artisan commands.
     *
     * @var array
     */
    protected $commands = [
        ListTheme::class    => 'command.theme.list',
        NewTheme::class     => 'command.theme.new',
        RemoveTheme::class  => 'command.theme.remove',
        ClearCache::class   => 'command.theme.clear_cache',
        RebuildCache::class => 'command.theme.rebuild_cache',
    ];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Register theme system
        $this->registerThemeSystem();

        // Replace the current view.finder app
        $this->registerThemeViewFinder();

        // Register commands
        $this->registerCommands($this->commands);
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        // Config handle
        $this->configBoot();

        // Theme handle
        $this->themeBoot();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array_merge([
            'themes',
        ], array_values($this->commands));
    }

    /**
     * Register the theme system.
     *
     * @return void
     */
    protected function registerThemeSystem()
    {
        $this->app->singleton('themes', function ($app) {
            return new ThemeSystem;
        });
    }

    /**
     * Register the theme view finder.
     *
     * @return void
     */
    protected function registerThemeViewFinder()
    {
        // From Laravel 6.0 and above, everything that 'view.finder' has done is perfect.
        // But with Laravel 5.8 and earlier, we need to register for the new 'view.finder'
        // app to replace the original one.
        $appVersion = $this->app->version();

        if (version_compare($appVersion, '6.0.0', '<')) {
            $this->app->bind('view.finder', function ($app) {
                return new ThemeViewFinder($app['files'], $app['config']['view.paths']);
            });
        }
    }

    /**
     * Register theme artisan commands.
     *
     * @param array $commands The artisan commands
     *
     * @return void
     */
    protected function registerCommands(array $commands = [])
    {
        foreach ($commands as $class => $name) {
            $this->app->singleton($name, function ($app) use ($class) {
                return $app->make($class);
            });
        }

        $this->commands(array_values($commands));
    }

    /**
     * Loading and publishing package's config.
     *
     * @return void
     */
    protected function configBoot()
    {
        $packageConfigPath = __DIR__ . '/config.php';
        $appConfigPath     = config_path('themes.php');

        $this->mergeConfigFrom($packageConfigPath, 'themes');
        $this->publishes([
            $packageConfigPath => $appConfigPath,
        ], 'config');
    }

    /**
     * Scan themes and active default theme.
     *
     * @return void
     */
    protected function themeBoot()
    {
        $themes = $this->app->make('themes');

        // Load available themes
        $themes->load();

        // Active default theme
        $defaultTheme = config('themes.default');

        if (!$themes->activated() && $defaultTheme) {
            $themes->uses($defaultTheme);
        }
    }
}
