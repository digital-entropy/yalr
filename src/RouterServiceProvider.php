<?php

namespace Jalameta\Router;

use Illuminate\Container\Container;
use Illuminate\Support\ServiceProvider;

/**
 * Router service provider.
 *
 * @author      veelasky <veelasky@gmail.com>
 */
class RouterServiceProvider extends ServiceProvider
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
            ], 'jps-router-config');
        }
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/routes.php', 'routes'
        );

        $this->app->singleton('jps.routing', function () {
            $factory = new RouterFactory(fn () => [
                Container::getInstance()['config'],
                Container::getInstance()['router'],
            ]);

            if (! RouterFactory::$fake) {
                $factory->register();
            }

            return $factory;
        });

        $this->app->alias('jps.routing', RouterFactory::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\RoutesCommand::class,
                Console\MakeCommand::class,
            ]);
        }
    }
}
