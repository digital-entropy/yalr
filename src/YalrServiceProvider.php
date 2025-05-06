<?php

namespace Dentro\Yalr;

use Illuminate\Container\Container;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as BaseRouteServiceProvider;

/**
 * Router service provider.
 *
 * @author      veelasky <veelasky@gmail.com>
 */
class YalrServiceProvider extends BaseRouteServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    #[\Override]
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
     */
    #[\Override]
    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(
            __DIR__.'/../config/routes.php', 'routes'
        );

        $this->app->singleton(RouterFactory::SERVICE_NAME, static fn(): \Dentro\Yalr\RouterFactory => new RouterFactory(static fn (): array => [
            Container::getInstance()['config'],
            Container::getInstance()['router'],
        ]));

        $this->app->alias(RouterFactory::SERVICE_NAME, RouterFactory::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\InstallCommand::class,
                Console\DisplayCommand::class,
                Console\MakeCommand::class,
                Console\GenerateCommand::class,
            ]);
        }
    }

    /**
     * Load the cached routes for the application.
     */
    #[\Override]
    protected function loadCachedRoutes(): void
    {
        $this->app->booted(function (): void {
            /** @var \Dentro\Yalr\RouterFactory $routerFactory */
            $routerFactory = $this->app->make(RouterFactory::SERVICE_NAME);
            $routerFactory->registerPreloads();
        });

        parent::loadCachedRoutes();
    }

    /**
     * Load the application routes.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException|\ReflectionException
     */
    #[\Override]
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
