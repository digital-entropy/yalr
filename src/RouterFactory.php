<?php

namespace Jalameta\Router;

use Illuminate\Routing\Router;
use JetBrains\PhpStorm\Pure;
use RuntimeException;
use Illuminate\Support\Collection;
use Jalameta\Router\Contracts\Bindable;

/**
 * Router Factory.
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
    protected array $routes = [];

    /**
     * List of all options.
     *
     * @var array
     */
    protected array $options = [];

    /**
     * @var \Jalameta\Router\RouteAttributeRegistrar
     */
    private RouteAttributeRegistrar $attributeRouteRegistrar;

    /**
     * RouterFactory constructor.
     *
     * @param Router $router
     */
    #[Pure]
    public function __construct(
        protected Router $router
    ) {
        $this->attributeRouteRegistrar = new RouteAttributeRegistrar($router);
    }

    /**
     * Create new route group.
     *
     * @param       $groupName
     * @param array $options
     * @param array $items
     *
     * @return \Jalameta\Router\RouterFactory
     */
    public function make($groupName, array $options = [], array $items = []): RouterFactory
    {
        if (array_key_exists($groupName, $this->routes) == false) {
            $this->routes[$groupName] = new Collection($items);
        } else {
            throw new RuntimeException("Route Group with key: `$groupName` already exist.");
        }

        $this->options[$groupName] = $options;

        return $this;
    }

    /**
     * Register to route group.
     *
     * @return void
     */
    public function register()
    {
        foreach ($this->groups() as $group) {
            $this->map($group);
        }
    }

    /**
     * Map all routes into laravel routes.
     *
     * @param $groupName
     *
     * @return void
     */
    public function map(string $groupName)
    {
        if (array_key_exists($groupName, $this->routes)) {
            $this->router->group($this->getOptions($groupName),
                fn() => collect($this->get($groupName))->each(fn($class) => $this->classRouteRegistrar($class)));
        }
    }

    /**
     * Get options for specific route group.
     *
     * @param $key
     * @return mixed
     */
    public function getOptions($key): mixed
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
    public function get($key): Collection
    {
        if (array_key_exists($key, $this->routes)) {
            return $this->routes[$key];
        }

        throw new RuntimeException("Route Group with key: `$key` doesn't exists.");
    }

    /**
     * List of all registered route groups.
     *
     * @return array
     */
    #[Pure]
    public function groups(): array
    {
        return array_keys($this->routes);
    }

    /**
     * Return all registered route.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->routes;
    }

    private function classRouteRegistrar(string $class)
    {
        $routeClass = new $class();

        if ($routeClass instanceof Bindable) {
            $routeClass->bind();
        } else {
            $this->attributeRouteRegistrar->registerClass($class);
        }
    }
}
