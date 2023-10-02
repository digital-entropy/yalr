<?php

namespace Dentro\Yalr\Attributes;

use Attribute;
use Illuminate\Support\Arr;

#[Attribute(Attribute::TARGET_METHOD)]
class Route implements RouteAttribute
{
    public array $middleware;
    public array $withoutMiddleware;

    public function __construct(
        public array | string $method,
        public string $uri,
        public ?string $name = null,
        array | string $middleware = [],
        array | string $withoutMiddleware = [],
    ) {
        $this->middleware = Arr::wrap($middleware);
        $this->withoutMiddleware = Arr::wrap($withoutMiddleware);
    }
}
