<?php

namespace Dentro\Yalr\Tests\Routes;

use Dentro\Yalr\Contracts\Bindable;
use Illuminate\Routing\Router;

class BinderRoute implements Bindable
{
    public function __construct(protected Router $router)
    {
    }

    public function bind(): void
    {
        $this->router->bind('user', static function (string $value): string {
            return 'User: ' . $value;
        });
    }
}
