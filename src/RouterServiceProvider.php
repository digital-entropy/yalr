<?php

namespace Jalameta\Router;

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
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/routes.php' => base_path('config/routes.php'),
            ], 'jps-router-config');
        }

        $this->registerRoutes();
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/routes.php', 'routes'
        );

        $this->app->singleton('jps.routing', function ($app) {
            $factory = new RouterFactory($app->router);

            $routes = config('routes.groups');

            foreach ($routes as $k => $v) {
                $factory->make($k, $v, config('routes.'.$k));
            }

            return $factory;
        });

        $this->app->alias('jps.routing', RouterFactory::class);

        $this->commands([
            Console\RoutesCommand::class,
            Console\MakeCommand::class,
        ]);
    }

    /**
     * Register all routes in application container.
     *
     * @return void
     */
    private function registerRoutes()
    {
        /**
         * @var \Jalameta\Router\RouterFactory
         */
        $router = $this->app['jps.routing'];

        $router->register();
    }
}
