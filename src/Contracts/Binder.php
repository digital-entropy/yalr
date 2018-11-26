<?php

namespace Jalameta\Router\Contracts;

/**
 * Self Registering Route Contracts.
 *
 * @author      veelasky <veelasky@gmail.com>
 */
interface Binder
{
    /**
     * Bind and register the current route.
     *
     * @return void
     */
    public static function bind();

    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register();
}
