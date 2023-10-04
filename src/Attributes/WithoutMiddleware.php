<?php

namespace Dentro\Yalr\Attributes;

use Attribute;
use Illuminate\Support\Arr;

#[Attribute(Attribute::TARGET_CLASS)]
class WithoutMiddleware implements RouteAttribute
{
    public array $withoutMiddleware = [];

    public function __construct(string | array $middleware = [])
    {
        $this->withoutMiddleware = Arr::wrap($middleware);
    }
}
