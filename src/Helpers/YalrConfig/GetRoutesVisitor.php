<?php

namespace Dentro\Yalr\Helpers\YalrConfig;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitor;

class GetRoutesVisitor extends AbstractRouteVisitor
{
    private array $routes;

    public function __construct(string $targetGroup, array &$routes)
    {
        parent::__construct($targetGroup);
        $this->routes = &$routes;
    }

    // Use enterNode for early exit once group is found and processed
    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Return_ && $node->expr instanceof Array_) {
            foreach ($node->expr->items as $item) {
                if ($item instanceof ArrayItem && $this->isTargetGroup($item)) {
                    if ($item->value instanceof Array_) {
                        // Extract routes from the group's array
                        foreach ($item->value->items as $routeItem) {
                            if ($routeItem->value instanceof ClassConstFetch) {
                                if ($routeItem->value->class instanceof Name) {
                                    $this->routes[] = $routeItem->value->class->toString() . '::class';
                                }
                            } elseif ($routeItem->value instanceof String_) {
                                $this->routes[] = $routeItem->value->value;
                            }
                        }
                    }
                    // Group found and processed, no need to traverse further within this branch
                    return NodeVisitor::STOP_TRAVERSAL;
                }
            }
        }
        return null; // Continue traversal otherwise
    }
}
