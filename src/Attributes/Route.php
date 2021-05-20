<?php

namespace Dentro\Yalr\Attributes;

use Attribute;
use Illuminate\Support\Arr;

#[Attribute(Attribute::TARGET_METHOD)]
class Route implements RouteAttribute
{
    public array $middleware;

    public function __construct(
        public array | string $method,
        public string $uri,
        public ?string $name = null,
        array | string $middleware = [],
    ) {
        $this->middleware = Arr::wrap($middleware);
    }
}
