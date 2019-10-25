<?php

namespace Jalameta\Router\Contracts;

/**
 * Interface Bindable
 * This interface is the same
 * with Binder Interface, removes static calling.
 * @see Binder
 *
 */
interface Bindable
{
    /**
     * Bind and register the current route.
     * remove static calling.
     *
     * @return void
     */
    public function bind();

    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register();

    /**
     * Performs callback after registering route.
     *
     * @return mixed
     */
    public function afterRegister();
}
