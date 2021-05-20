<?php

namespace Dentro\Yalr\Tests;

use Closure;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Arr;
use Dentro\Yalr\RouterFactory;
use Dentro\Yalr\RouteServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;


class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        RouterFactory::fake();

        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            RouteServiceProvider::class,
        ];
    }

    public function getTestPath(string $directory = null): string
    {
        return __DIR__ . ($directory ? '/' . $directory : '');
    }

    public function assertRegisteredRoutesCount(int $expectedNumber): self
    {
        $actualNumber = $this->getRouteCollection()->count();

        static::assertSame($expectedNumber, $actualNumber);

        return $this;
    }

    public function assertRouteRegistered(
        string $httpMethod = 'get',
        string $uri = 'my-method',
        string $controller = null,
        string $controllerMethod = 'myMethod',
        ?string $name = null,
        ?string $domain = null,
        string | array $middleware = [],
    ): self {
        if (! \is_array($middleware)) {
            $middleware = Arr::wrap($middleware);
        }

        $routeRegistered = collect($this->getRouteCollection()->getRoutes())
            ->contains(function (Route $route) use ($name, $middleware, $controllerMethod, $controller, $uri, $httpMethod, $domain) {
                if (!\in_array(strtoupper($httpMethod), $route->methods, true)) {
                    return false;
                }

                if ($route->uri() !== $uri) {
                    return false;
                }

                if (! $route->getAction('uses') instanceof Closure) {
                    if (\get_class($route->getController()) !== $controller) {
                        return false;
                    }

                    if ($route->getActionMethod() !== $controllerMethod) {
                        return false;
                    }
                }


                if (array_diff($route->middleware(), $middleware)) {
                    return false;
                }

                if ($route->getName() !== $name) {
                    return false;
                }

                if ($route->getDomain() !== $domain) {
                    return false;
                }

                return true;
            });

        static::assertTrue($routeRegistered, 'The expected route was not registered');

        return $this;
    }

    protected function getRouteCollection(): RouteCollection
    {
        return app()->router->getRoutes();
    }

    protected function routerFactory(): RouterFactory
    {
        return $this->app->make(RouterFactory::class);
    }
}
