<?php

namespace Dentro\Yalr\Tests\Routes;

use Dentro\Yalr\BaseRoute;

class BinderRoute extends BaseRoute
{
    public function register(): void
    {
        $this->router->bind('user', function ($value) {
            return 'User: ' . $value;
        });
    }
}
