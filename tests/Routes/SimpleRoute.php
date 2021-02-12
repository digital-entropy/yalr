<?php

namespace Jalameta\Router\Tests\Routes;

use Jalameta\Router\BaseRoute;

class SimpleRoute extends BaseRoute
{
    public function register()
    {
        $this->router->get($this->prefix('foo'), [
            'as' => 'foo',
            'uses' => fn() => 'bar',
        ]);
    }
}
