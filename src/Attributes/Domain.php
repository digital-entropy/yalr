<?php

namespace Jalameta\Router\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Domain implements RouteAttribute
{
    public function __construct(
        public string $domain
    ) {
    }
}
