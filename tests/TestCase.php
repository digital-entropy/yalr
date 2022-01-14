<?php

namespace Dentro\Yalr\Tests;

use Closure;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\CompiledRouteCollection;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
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

    protected function getRouteCollection(): RouteCollection|CompiledRouteCollection
    {
        return $this->getRouter()->getRoutes();
    }

    protected function getRouter(): Router
    {
        return $this->app->make('router');
    }

    protected function cacheRoute(): void
    {
        $files = $this->app->make(Filesystem::class);

        $stub = $files->get(__DIR__.'/../vendor/laravel/framework/src/Illuminate/Foundation/Console/stubs/routes.stub');

        $content = str_replace('{{routes}}', var_export($this->getRouteCollection()->compile(), true), $stub);

        $files->put(
            $this->app->getCachedRoutesPath(), $content,
        );

        static::assertTrue(
            $files->exists(base_path('bootstrap/cache/routes-v7.php'))
        );

        if (isset($this->app)) {
            $this->reloadApplication();
        }


        $this->beforeApplicationDestroyed(function () use ($files) {
            $files->delete(
                base_path('bootstrap/cache/routes-v7.php'),
                ...$files->glob(base_path('routes/testbench-*.php'))
            );

            sleep(1);
        });
    }

    protected function routerFactory(): RouterFactory
    {
        return $this->app->make(RouterFactory::class);
    }
}
