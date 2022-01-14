<?php

namespace Dentro\Yalr;

use Illuminate\Container\Container;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as BaseRouteServiceProvider;

/**
 * Router service provider.
 *
 * @author      veelasky <veelasky@gmail.com>
 */
class RouteServiceProvider extends BaseRouteServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/routes.php' => base_path('config/routes.php'),
            ], 'yalr-config');
        }
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(
            __DIR__.'/../config/routes.php', 'routes'
        );

        $this->app->singleton(RouterFactory::SERVICE_NAME, function () {
            return new RouterFactory(fn () => [
                Container::getInstance()['config'],
                Container::getInstance()['router'],
            ]);
        });

        $this->app->alias(RouterFactory::SERVICE_NAME, RouterFactory::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\InstallCommand::class,
                Console\DisplayCommnad::class,
                Console\MakeCommand::class,
            ]);
        }
    }

    /**
     * Load the cached routes for the application.
     *
     * @return void
     */
    protected function loadCachedRoutes(): void
    {
        parent::loadCachedRoutes();

        $this->app->booted(function () {
            /** @var \Dentro\Yalr\RouterFactory $routerFactory */
            $routerFactory = $this->app->make(RouterFactory::SERVICE_NAME);
            $routerFactory->registerPreloads();
        });
    }

    /**
     * Load the application routes.
     *
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException|\ReflectionException
     */
    protected function loadRoutes(): void
    {
        /** @var \Dentro\Yalr\RouterFactory $routerFactory */
        $routerFactory = $this->app->make(RouterFactory::SERVICE_NAME);
        $routerFactory->registerPreloads();
        if (! RouterFactory::$fake) {
            $routerFactory->register();
        }
    }
}
