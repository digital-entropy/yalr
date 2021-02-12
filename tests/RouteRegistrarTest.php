<?php

namespace Jalameta\Router\Tests;

use Jalameta\Router\Tests\Routes\SimpleRoute;

class RouteRegistrarTest extends TestCase
{
    public function test_can_register_a_route()
    {
        $this->routeFactory
            ->make(groupName: 'foo', items: [SimpleRoute::class])
            ->map('foo');

        $this->assertRegisteredRoutesCount(1);
        $this->assertRouteRegistered(uri: 'foo', name: 'foo');
    }
}
