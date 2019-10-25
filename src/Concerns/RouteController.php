<?php

namespace Jalameta\Router\Concerns;

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
    public function controller()
    {
        return '';
    }

    /**
     * Use controller method.
     *
     * @param $method string
     * @param $controller string
     *
     * @return string
     */
    public function uses($method, $controller = null)
    {
        if (! method_exists($this, 'controller') and empty($controller)) {
            throw new RuntimeException('Controller is not defined.');
        }

        $controller = empty($controller) ? $this->controller() : $controller;

        return $controller.'@'.$method;
    }
}
