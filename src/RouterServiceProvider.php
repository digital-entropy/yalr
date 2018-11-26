<?php

namespace Jalameta\Router;

use Illuminate\Support\ServiceProvider;

/**
 * Router service provider
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
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/routes.php' => config_path('routes.php')
            ], 'jps-router-config');
        }
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('jps.routing', function () {
            $factory = new RouterFactory();

            return $factory;
        });

        $this->app->alias('jps.routing', RouterFactory::class);

        $this->commands([
            Console\InstallCommand::class
        ]);
    }
}