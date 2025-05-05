<?php

namespace Dentro\Yalr\Tests;

use Closure;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Routing\CompiledRouteCollection;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use Dentro\Yalr\RouterFactory;
use Dentro\Yalr\YalrServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        // Use a static method for mocking instead of deprecated "fake" method
        $this->mockRouterFactory();

        parent::setUp();
    }

    /**
     * Mock the RouterFactory
     */
    protected function mockRouterFactory(): void
    {
        // Modern way to mock static functionality
        if (method_exists(RouterFactory::class, 'fake')) {
            RouterFactory::fake();
        } elseif (method_exists(RouterFactory::class, 'shouldReceive')) {
            RouterFactory::shouldReceive('create')->andReturnUsing(function(): \Dentro\Yalr\RouterFactory {
                return new RouterFactory();
            });
        }
    }

    protected function getPackageProviders($app): array
    {
        return [
            YalrServiceProvider::class,
        ];
    }

    public function getTestPath(string|null $directory = null): string
    {
        return __DIR__ . ($directory ? '/' . $directory : '');
    }

    public function assertRegisteredRoutesCount(int $expectedNumber): self
    {
        $actualNumber = $this->getRouteCollection()->count();

        self::assertSame($expectedNumber, $actualNumber);

        return $this;
    }

    public function assertRouteRegistered(
        string $httpMethod = 'get',
        string $uri = 'my-method',
        string|null $controller = null,
        string $controllerMethod = 'myMethod',
        string|null $name = null,
        string|null $domain = null,
        string | array $middleware = [],
    ): self {
        if (! \is_array($middleware)) {
            $middleware = Arr::wrap($middleware);
        }

        $routeRegistered = collect($this->getRouteCollection()->getRoutes())
            ->contains(static function (Route $route) use ($name, $middleware, $controllerMethod, $controller, $uri, $httpMethod, $domain): bool {
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
                return $route->getDomain() === $domain;
            });

        self::assertTrue($routeRegistered, 'The expected route was not registered');

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

        /**
         * @see FilesystemServiceProvider::serveFiles()
         * This method will disturb the caching route because it has a closure action.
         * The generated route at ...bootstrap/cache/routes-v7.php will be broken
         * and generate something like \Closure::__set_state()
         */

        // Filter out routes with closure actions before compiling to avoid serialization issues
        $routes = $this->getRouteCollection();
        $compilableRoutes = $this->sanitizeClosure($routes);

        $content = str_replace('{{routes}}', var_export($compilableRoutes->compile(), true), $stub);

        $files->put(
            $this->app->getCachedRoutesPath(), $content,
        );

        self::assertTrue(
            $files->exists(base_path('bootstrap/cache/routes-v7.php'))
        );

        if (isset($this->app)) {
            $this->reloadApplication();
        }

        $this->beforeApplicationDestroyed(static function () use ($files) {
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

    /**
     * Filter out routes with closure actions to prevent serialization issues during route caching
     *
     * @param CompiledRouteCollection|RouteCollection $routes
     * @return RouteCollection
     */
    protected function sanitizeClosure(CompiledRouteCollection|RouteCollection $routes): RouteCollection
    {
        $filteredRoutes = new RouteCollection();

        foreach ($routes->getRoutes() as $route) {
            if ($route->getAction('uses') instanceof Closure) {
                $route->setAction([]);
            }

            $filteredRoutes->add($route);
        }

        return $filteredRoutes;
    }
}
