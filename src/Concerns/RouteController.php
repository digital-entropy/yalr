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
     *
     * @return string
     */
    public function controller(): string
    {
        return '';
    }

    /**
     * Use controller method.
     *
     * @param $method string
     * @param string|null $controller
     *
     * @return string
     */
    public function uses(string $method, string $controller = null): string
    {
        if (empty($controller) && ! method_exists($this, 'controller')) {
            throw new RuntimeException('Controller is not defined.');
        }

        $controller = empty($controller) ? $this->controller() : $controller;

        return $controller.'@'.$method;
    }
}
