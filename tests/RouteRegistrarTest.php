<?php

namespace Dentro\Yalr\Tests;

use Dentro\Yalr\Tests\Controllers\GetTestController;
use Dentro\Yalr\Tests\Routes\SimpleRoute;

class RouteRegistrarTest extends TestCase
{
    public function test_can_register_a_route(): void
    {
        $this->routerFactory()
            ->make(groupName: 'foo', items: [SimpleRoute::class])
            ->register();

        $this->assertRegisteredRoutesCount(1);
        $this->assertRouteRegistered(uri: 'foo', name: 'foo');
    }

    public function test_using_attribute_registrar(): void
    {
        $this->routerFactory()
            ->make(groupName: 'foo', items: [GetTestController::class])
            ->register();

        $this->assertRegisteredRoutesCount(5);
        $this->assertRouteRegistered(
            uri: 'test',
            controller: GetTestController::class,
            controllerMethod: 'index',
            name: 'test.index',
        );
    }
}
