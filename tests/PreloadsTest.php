<?php

namespace Dentro\Yalr\Tests;

use Dentro\Yalr\Tests\Controllers\GetTestController;
use Dentro\Yalr\Tests\Routes\BinderRoute;

class PreloadsTest extends TestCase
{

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('routes.preloads', [
            BinderRoute::class,
        ]);
    }

    public function test_preloads_called_when_route_cached(): void
    {
        $this->routerFactory()
            ->make(groupName: 'foo', items: [GetTestController::class])
            ->register();

        $this->cacheRoute();

        $this->assertRegisteredRoutesCount(5);
        static::assertTrue($this->app->routesAreCached());

        static::assertNotNull($this->getRouter()->getBindingCallback('user'));
    }

    public function test_preloads_called_when_route_not_cached(): void
    {
        $this->routerFactory()
            ->make(groupName: 'foo', items: [GetTestController::class])
            ->register();

        $this->assertRegisteredRoutesCount(5);
        static::assertNotTrue($this->app->routesAreCached());

        static::assertNotNull($this->getRouter()->getBindingCallback('user'));
    }
}
