<?php

namespace Dentro\Yalr\Contracts;

interface Registerable
{
    /**
     * Register routes handled by this class.
     */
    public function register(): void;

    /**
     * Performs callback after registering route.
     */
    public function afterRegister(): void;
}
