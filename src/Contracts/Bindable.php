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
     */
    public function bind(): void;
}
