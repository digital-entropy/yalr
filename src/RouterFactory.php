<?php

namespace Dentro\Yalr;

use Closure;
use Illuminate\Config\Repository;
use Illuminate\Routing\Router;
use JetBrains\PhpStorm\Pure;
use OutOfBoundsException;
use ReflectionClass;
use RuntimeException;
use Illuminate\Support\Collection;
use Dentro\Yalr\Contracts\Bindable;
use function array_key_exists;
use function in_array;

/**
 * Router Factory.
 *
 * @author      veelasky <veelasky@gmail.com>
 */
class RouterFactory
{
    public static bool $fake = false;

    public const SERVICE_NAME = 'yalr';

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
     * RouterFactory constructor.
     *
     * @param \Closure $resolver should return [Config, Router]
     */
    #[Pure]
    public function __construct(
        protected Closure $resolver
    ) {}

    /**
     * @param bool $fake
     */
    public static function fake(bool $fake = true): void
    {
        self::$fake = $fake;
    }

    /**
     * Create new route group.
     *
     * @param       $groupName
     * @param array $options
     * @param array $items
     *
     * @return \Dentro\Yalr\RouterFactory
     */
    public function make($groupName, array $options = [], array $items = []): self
    {
        if (! \array_key_exists($groupName, $this->routes)) {
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
    public function register(): void
    {
        $resolver = $this->resolver;

        /**
         * @var  \Illuminate\Config\Repository $config
         * @var  Router $router
         */
        [$config, $router] = $resolver();

        $this->resolveRouteFromConfig($config);

        foreach ($this->groups() as $group) {
            $this->map($router, $group);
        }
    }

    protected function resolveRouteFromConfig(Repository $config): void
    {
        $routes = $config->get('routes.groups');

        foreach ($routes as $groupName => $options) {
            if ($config->get('routes.'.$groupName) === null) {
                throw new OutOfBoundsException('group `'.$groupName.'` in config.routes doesn\'t exists.');
            }

            $this->make($groupName, $options, $config->get('routes.'.$groupName));
        }
    }

    /**
     * Map all routes into laravel routes.
     *
     * @param \Illuminate\Routing\Router $router
     * @param string $groupName
     *
     * @return void
     */
    public function map(Router $router, string $groupName): void
    {
        if (\array_key_exists($groupName, $this->routes)) {
            $router->group($this->getOptions($groupName),
                fn() => collect($this->get($groupName))->each(
                    fn($class) => $this->classRouteRegistrar($router, $class)
                )
            );
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
        if (\array_key_exists($key, $this->routes)) {
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

    /**
     * Register class
     *
     * @param \Illuminate\Routing\Router $router
     * @param string $class
     * @throws \ReflectionException
     */
    private function classRouteRegistrar(Router $router, string $class): void
    {
        $reflectionClass = new ReflectionClass($class);

        if (\in_array(Bindable::class, $reflectionClass->getInterfaceNames(), true)) {
            /** @var \Dentro\Yalr\Contracts\Bindable $bindableClass */
            $bindableClass = $reflectionClass->newInstance($router);
            $bindableClass->bind();
        } else {
            (new RouteAttributeRegistrar($router))->registerClass($class);
        }
    }
}
