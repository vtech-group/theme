<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Themes storage path
    |--------------------------------------------------------------------------
    |
    | The path to directory used to store Views of all themes. This path can be
    | outside default views path.
    |
    | Leave it null if you will put your themes in the default views folder
    | (as defined in config/view.php)
    |
    */

    'path' => resource_path('themes'),

    /*
    |--------------------------------------------------------------------------
    | Sub-folder level of storing themes
    |--------------------------------------------------------------------------
    |
    | Typically, themes are stored in each separate directory located in the
    | above configured path. So, by default, the system will automatically scan
    | for themes at the first level sub-folder of this path. If you want to put
    | themes at a deeper sub-folder level, set it up here.
    |
    | Example: If your themes located in the above configured path have a
    | directory structure of "path/to/theme-name", you have to set this value
    | to 3.
    |
    */

    'sub_folder_level' => 1,

    /*
    |--------------------------------------------------------------------------
    | Assets storage folder
    |--------------------------------------------------------------------------
    |
    | The sub-folder in the project's public directory used to store Assets of
    | all themes.
    |
    | Leave it null if you will put your themes assets in the public directory
    |
    */

    'assets_folder' => 'themes',

    /*
    |--------------------------------------------------------------------------
    | Theme model class
    |--------------------------------------------------------------------------
    |
    | Each valid theme scanned in the above configured themes storage directory
    | will be initialized by a model class. By default, the system will use the
    | model class "Vtech\Theme\Theme". You can set up using your custom class.
    | What you need is that your class must inherit from this default class.
    |
    | Example: App\Theme::class
    |
    */

    'theme_model' => null,

    /*
    |--------------------------------------------------------------------------
    | Debug behavior
    |--------------------------------------------------------------------------
    |
    | If the theme loading process fails, the system will throw an exception,
    | this is useful for development environment.
    |
    | But when deploying the app on production environment, you should turn off
    | this mechanism by setting one of the following values:
    |
    | - ignore        : continue without recording any errors
    | - log_emergency : continue but write an emergency message to the log
    | - log_alert     : continue but write an alert message to the log
    | - log_critical  : continue but write a critical message to the log
    | - log_error     : continue but write an error message to the log
    | - log_warning   : continue but write a warning message to the log
    | - log_notice    : continue but write a notice message to the log
    | - log_info      : continue but write an info message to the log
    | - log_debug     : continue but write a debug message to the log
    |
    */

    'debug_behavior' => [
        'default'    => null,
        'exceptions' => [
            // Vtech\Theme\Exceptions\InvalidThemeModel::class    => null,
            // Vtech\Theme\Exceptions\InvalidThemeName::class     => null,
            // Vtech\Theme\Exceptions\ThemeNotFound::class        => null,
            // Vtech\Theme\Exceptions\ThemeAlreadyExists::class   => null,
            // Vtech\Theme\Exceptions\InvalidThemeJsonFile::class => null,
            // Vtech\Theme\Exceptions\InvalidCacheFile::class     => null,
            // Vtech\Theme\Exceptions\AssetNotFound::class        => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default theme
    |--------------------------------------------------------------------------
    |
    | This setting allow to set the theme that you want to activated by default.
    |
    */

    'default' => null,

    /*
    |--------------------------------------------------------------------------
    | Cache themes configuration
    |--------------------------------------------------------------------------
    |
    | Cache theme.json configuration files that are located in each theme's
    | folder in order to avoid searching theme settings in the filesystem for
    | each request.
    |
    */

    'cache' => false,

    /*
    |--------------------------------------------------------------------------
    | Reservation themes
    |--------------------------------------------------------------------------
    |
    | These themes are those that do not exist in the configured themes storage
    | path (no theme.json file). The goal is to create themes that don't have
    | views but can use assets. The system will load these themes before
    | scanning the themes that exist in storage path.
    |
    | The structure for this setting is:
    |
    |   'reservations' = [
    |       'theme-name-1' => [
    |           // Setup the theme's attributes here
    |           'attribute' => value,
    |       ],
    |       'theme-name-2' => [
    |           ...
    |       ],
    |   ]
    |
    */

    'reservations' => [],
];
