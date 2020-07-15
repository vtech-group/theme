<?php

namespace Vtech\Theme\Traits;

use Vtech\Theme\Contracts\ThemeModel;
use Vtech\Theme\Exceptions\InvalidThemeModel;
use Vtech\Theme\Theme;

trait InitTheme
{
    use Debug;

    /**
     * Initialize a theme instance.
     *
     * @param array $attributes The theme attributes
     *
     * @return Theme|null
     */
    protected function initTheme(array $attributes = [])
    {
        $model = config('themes.theme_model');
        $model = $model ?: Theme::class;

        if (!is_subclass_of($model, ThemeModel::class)) {
            $this->tryCall(function ($className) {
                throw new InvalidThemeModel($className);
            }, $model);

            return null;
        }

        return $this->tryCall(function ($model, $attributes) {
            return new $model($attributes);
        }, [$model, $attributes]);
    }
}
