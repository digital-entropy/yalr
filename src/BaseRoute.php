<?php

namespace Dentro\Yalr;

use Dentro\Yalr\Contracts\Bindable;
use Dentro\Yalr\Concerns\RouteController;
use Illuminate\Routing\Router;
use JetBrains\PhpStorm\Pure;

/**
 * Base router class.
 *
 * @author      veelasky <veelasky@gmail.com>
 */
abstract class BaseRoute implements Bindable
{
    use RouteController;

    /**
     * Route path prefix.
     *
     * @var string
     */
    protected string $prefix = '/';

    /**
     * Registered route name.
     *
     * @var string
     */
    protected string $name;

    /**
     * Middleware used in route.
     *
     * @var array|string
     */
    protected array|string $middleware;

    /**
     * Route for specific domain.
     *
     * @var string
     */
    protected string $domain;

    /**
     * Route for specific regular expression.
     *
     * @var array|string
     */
    protected array|string $regex;

    /**
     * SelfBindingRoute constructor.
     */
    public function __construct(
        protected Router $router
    ) {}

    /**
     * Bind and register the current route.
     *
     * @return void
     */
    public function bind(): void
    {
        $this->router->group($this->getRouteGroupOptions(), function () {
            $this->register();
        });

        $this->afterRegister();
    }

    /**
     * Perform after register callback.
     *
     * @return void
     */
    public function afterRegister(): void
    {
        //
    }

    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    abstract public function register(): void;

    /**
     * Get route prefix.
     *
     * @param string $path
     *
     * @return string
     */
    #[Pure]
    public function prefix(string $path = '/'): string
    {
        return $this->prefix === '/' ? $path : $this->mergePath($path);
    }

    /**
     * Remove slash
     *
     * @param $path
     * @return string
     */
    #[Pure]
    private function removeSlashes($path): string
    {
        return ltrim(rtrim($path, '/'), '/');
    }

    /**
     * Merge path from prefix property and path input
     *
     * @param $path
     * @return string
     */
    #[Pure]
    private function mergePath($path): string
    {
        $prefix = $this->removeSlashes($this->prefix);
        $path = $this->removeSlashes($path);

        return $prefix . '/' . $path;
    }

    /**
     * Get route name.
     *
     * @param string|null $suffix
     *
     * @return string
     */
    #[Pure]
    public function name(string $suffix = null): string
    {
        if (empty($suffix)) {
            return $this->getBaseName(false);
        }

        return (empty($this->name ?? null) ? $suffix : $this->getBaseName() . $suffix);
    }

    /**
     * Get Base name.
     *
     * @param bool $dotSuffix
     *
     * @return string
     */
    private function getBaseName(bool $dotSuffix = true): string
    {
        return ($this->name ?? '') . ($dotSuffix ? '.' : '');
    }

    /**
     * Get Route Binder Options.
     *
     * @return array
     */
    public function getRouteGroupOptions(): array
    {
        $options = [];

        if (isset($this->middleware) && !empty($this->middleware)) {
            $options['middleware'] = $this->middleware;
        }

        if (isset($this->domain) && !empty($this->domain)) {
            $options['domain'] = $this->domain;
        }

        if (!empty($this->regex)) {
            $options['regex'] = $this->regex;
        }

        return $options;
    }
}
