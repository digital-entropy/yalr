<?php

namespace Dentro\Yalr\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Name implements RouteAttribute
{
    public function __construct(
        public string $name,
        bool $dotPrefix = false,
        bool $dotSuffix = false,
    ) {
        if ($dotPrefix) {
            $this->name = '.'.$this->name;
        }

        if ($dotSuffix) {
            $this->name = $this->name.'.';
        }
    }
}
