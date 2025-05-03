<?php

namespace Dentro\Yalr;

use Dentro\Yalr\Contracts\Bindable;
use Dentro\Yalr\Concerns\RouteController;
use Dentro\Yalr\Contracts\Registerable;
use Illuminate\Routing\Router;
use JetBrains\PhpStorm\Pure;

/**
 * Base router class.
 *
 * @author      veelasky <veelasky@gmail.com>
 */
abstract class BaseRoute implements Bindable, Registerable
{
    use RouteController;

    /**
     * Route path prefix.
     */
    protected string $prefix = '/';

    /**
     * Registered route name.
     */
    protected string $name;

    /**
     * Middleware used in route.
     */
    protected array|string $middleware;

    /**
     * Middleware excluded in route.
     */
    protected array|string $withoutMiddleware;

    /**
     * Route for specific domain.
     */
    protected string $domain;

    /**
     * Route for specific regular expression.
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
     */
    public function bind(): void
    {
        $this->router->group($this->getRouteGroupOptions(), function (): void {
            $this->register();
        });

        $this->afterRegister();
    }

    /**
     * Perform after register callback.
     */
    public function afterRegister(): void
    {
        //
    }

    /**
     * Register routes handled by this class.
     */
    abstract public function register(): void;

    /**
     * Get route prefix.
     *
     *
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
     */
    #[Pure]
    private function removeSlashes(string $path): string
    {
        return ltrim(rtrim($path, '/'), '/');
    }

    /**
     * Merge path from prefix property and path input
     *
     * @param $path
     */
    #[Pure]
    private function mergePath(string $path): string
    {
        $prefix = $this->removeSlashes($this->prefix);
        $path = $this->removeSlashes($path);

        return $prefix . '/' . $path;
    }

    /**
     * Get route name.
     *
     * @param string|null $suffix
     */
    #[Pure]
    public function name(string $suffix = null): string
    {
        if ($suffix === null || $suffix === '' || $suffix === '0') {
            return $this->getBaseName(false);
        }

        return (($this->name ?? null) === null || ($this->name ?? null) === '' || ($this->name ?? null) === '0' ? $suffix : $this->getBaseName() . $suffix);
    }

    /**
     * Get Base name.
     *
     *
     */
    private function getBaseName(bool $dotSuffix = true): string
    {
        return ($this->name ?? '') . ($dotSuffix ? '.' : '');
    }

    /**
     * Get Route Binder Options.
     */
    public function getRouteGroupOptions(): array
    {
        $options = [];

        if (isset($this->middleware) && (isset($this->middleware) && ($this->middleware !== '' && $this->middleware !== '0' && $this->middleware !== []))) {
            $options['middleware'] = $this->middleware;
        }

        if (isset($this->withoutMiddleware) && (isset($this->withoutMiddleware) && ($this->withoutMiddleware !== '' && $this->withoutMiddleware !== '0' && $this->withoutMiddleware !== []))) {
            $options['withoutMiddleware'] = $this->withoutMiddleware;
        }

        if (isset($this->domain) && (isset($this->domain) && ($this->domain !== '' && $this->domain !== '0'))) {
            $options['domain'] = $this->domain;
        }

        if (isset($this->regex) && ($this->regex !== '' && $this->regex !== '0' && $this->regex !== [])) {
            $options['regex'] = $this->regex;
        }

        return $options;
    }
}
