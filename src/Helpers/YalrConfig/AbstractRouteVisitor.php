<?php

namespace Dentro\Yalr\Helpers\YalrConfig;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Scalar\String_;

abstract class AbstractRouteVisitor extends NodeVisitorAbstract
{
    protected string $targetGroup;

    public function __construct(string $targetGroup)
    {
        $this->targetGroup = $targetGroup;
    }

    protected function isTargetGroup(ArrayItem $item): bool
    {
        return $item->key instanceof String_ &&
               $item->key->value === $this->targetGroup;
    }
}
