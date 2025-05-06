<?php

namespace Dentro\Yalr\Concerns;

use RuntimeException;

/**
 * Trait RouteController.
 *
 * @author rendyananta<rendy, rendyananta66@gmail.com>
 */
trait RouteController
{
    /**
     * Get controller namespace.
     */
    public function controller(): string
    {
        return '';
    }

    /**
     * Use controller method.
     *
     * @param $method string
     */
    public function uses(string $method, string|null $controller = null): string
    {
        if (($controller === null || $controller === '' || $controller === '0') && ! method_exists($this, 'controller')) {
            throw new RuntimeException('Controller is not defined.');
        }

        $controller = $controller === null || $controller === '' || $controller === '0' ? $this->controller() : $controller;

        return $controller.'@'.$method;
    }
}
