<?php

namespace Dentro\Yalr\Tests\Routes;

use Dentro\Yalr\BaseRoute;

class SimpleRoute extends BaseRoute
{
    public function register(): void
    {
        $this->router->get($this->prefix('foo'), [
            'as' => 'foo',
            'uses' => fn() => 'bar',
        ]);
    }
}
