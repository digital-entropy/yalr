<?php

namespace Dentro\Yalr\Contracts;

use Illuminate\Routing\Router;

/**
 * Interface Bindable
 * This interface is the same
 * with Binder Interface, removes static calling.
 * @see Binder
 *
 */
interface Bindable
{

    public function __construct(Router $router);

    /**
     * Bind and register the current route.
     * remove static calling.
     *
     * @return void
     */
    public function bind(): void;

    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void;

    /**
     * Performs callback after registering route.
     *
     * @return void
     */
    public function afterRegister(): void;
}
