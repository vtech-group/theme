<?php

namespace Vtech\Theme\Traits;

use Exception;

/**
 * The Debug trait.
 *
 * @package vtech/theme
 *
 * @author  Jackie Do <anhvudo@gmail.com>
 */
trait Debug
{
    /**
     * Try to call a callable.
     * If an error occurs, execute the pre-configured debugging behavior.
     *
     * @param callable $callable   The request
     * @param array    $parameters The parametares pass to request
     * @param mixed    $default    The value will be return if error
     *
     * @return mixed
     */
    protected function tryCall(callable $callable, $parameters = [], $default = null)
    {
        try {
            return call_user_func_array($callable, (array) $parameters);
        } catch (Exception $exception) {
            $exceptionClass = get_class($exception);
            $behavior       = strtolower(config('themes.debug_behavior.' . $exceptionClass, config('themes.debug_behavior.default')));

            if ('ignore' === $behavior) {
                return $default;
            }

            if ('log' == substr($behavior, 0, 3)) {
                $logLevel = substr($behavior, 4);
                $logLevel = $logLevel ?: 'error';

                app('log')->{$logLevel}($exception);

                return $default;
            }

            throw $exception;
        }
    }
}
