<?php

namespace Dentro\Yalr\Contracts;

interface Registerable
{
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
