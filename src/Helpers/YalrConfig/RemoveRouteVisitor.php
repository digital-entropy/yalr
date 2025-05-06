<?php

namespace Dentro\Yalr\Helpers\YalrConfig;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;

class RemoveRouteVisitor extends AbstractRouteVisitor
{
    private string $routeToRemove;
    private bool $groupFound = false;
    private bool $routeRemoved = false;

    public function __construct(string $targetGroup, string $routeToRemove)
    {
        parent::__construct($targetGroup);
        $this->routeToRemove = $routeToRemove;
    }

    public function leaveNode(Node $node): ?Node
    {
        if ($node instanceof Node\Stmt\Return_ && $node->expr instanceof Array_) {
            foreach ($node->expr->items as $item) {
                if ($item instanceof ArrayItem && $this->isTargetGroup($item)) {
                    $this->groupFound = true;

                    if (!$item->value instanceof Array_) {
                        continue; // Cannot remove from non-array group value
                    }

                    // Find and remove route
                    $originalItems = $item->value->items;
                    $newItems = [];
                    $removed = false;
                    foreach ($originalItems as $routeItem) {
                        $currentRouteValue = null;
                        if ($routeItem->value instanceof ClassConstFetch) {
                            if ($routeItem->value->class instanceof Name) {
                                $currentRouteValue = $routeItem->value->class->toString() . '::class';
                            }
                        } elseif ($routeItem->value instanceof String_) {
                            $currentRouteValue = $routeItem->value->value;
                        }

                        if ($currentRouteValue === $this->routeToRemove) {
                            // Don't add this item to the new list
                            $removed = true;
                            $this->routeRemoved = true;
                        } else {
                            $newItems[] = $routeItem; // Keep this item
                        }
                    }

                    // If an item was removed, update the items list for the group
                    if ($removed) {
                        $item->value->items = $newItems;
                    }
                    // Stop searching once the target group is found and processed
                    break;
                }
            }
            // Return the potentially modified node
            return $node;
        }
        // Returning null means "keep node as is" unless modification happened above
        return null;
    }

    public function hasGroupFound(): bool
    {
        return $this->groupFound;
    }

    public function hasRouteRemoved(): bool
    {
        return $this->routeRemoved;
    }
}
