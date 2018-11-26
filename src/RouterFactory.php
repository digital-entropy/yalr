<?php

namespace Jalameta\Router;

use RuntimeException;
use Illuminate\Support\Collection;

/**
 * Router Factory
 *
 * @author      veelasky <veelasky@gmail.com>
 */
class RouterFactory
{
    /**
     * Route groups.
     *
     * @var array
     */
    protected $routes = [];

    /**
     * List of all options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Create new route group.
     *
     * @param       $key
     * @param array $options
     * @param array $items
     *
     * @return \Illuminate\Support\Collection
     */
    public function make($key, array $options = [], array $items = [])
    {
        if (array_key_exists($key, $this->routes) == false) {
            $this->routes[$key] = new Collection($items);
        } else {
            throw new RuntimeException("Route Group with key: `$key` already exist.");
        }

        $this->options[$key] = $options;

        return $this->get($key);
    }

    /**
     * Get options for specific route group.
     *
     * @param $key
     * @return mixed
     */
    public function getOptions($key)
    {
        return $this->options[$key];
    }

    /**
     * Get Route Group container by its key.
     *
     * @param $key
     *
     * @return \Illuminate\Support\Collection
     */
    public function get($key)
    {
        if (array_key_exists($key, $this->routes)) {
            return $this->routes[$key];
        }

        throw new RuntimeException("Route Group with key: `$key` doesn't exists.");
    }

    /**
     * Return all registered route.
     *
     * @return array
     */
    public function all()
    {
        return $this->routes;
    }
}