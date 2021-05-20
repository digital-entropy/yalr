<?php

namespace Dentro\Yalr\Attributes;

use Attribute;
use Illuminate\Routing\Router;

#[Attribute(Attribute::TARGET_METHOD)]
class Any extends Route
{
    public function __construct(
        string $uri,
        ?string $name = null,
        array | string $middleware = [],
    ) {
        parent::__construct(
            method: Router::$verbs,
            uri: $uri,
            name: $name,
            middleware: $middleware,
        );
    }
}
