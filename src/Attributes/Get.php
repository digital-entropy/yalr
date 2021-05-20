<?php

namespace Dentro\Yalr\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Get extends Route
{
    public function __construct(
        string $uri,
        ?string $name = null,
        array | string $middleware = [],
    ) {
        parent::__construct(
            method: ['GET', 'HEAD'],
            uri: $uri,
            name: $name,
            middleware: $middleware,
        );
    }
}
