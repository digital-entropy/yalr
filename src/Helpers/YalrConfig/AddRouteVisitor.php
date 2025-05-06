<?php

namespace Dentro\Yalr\Helpers\YalrConfig;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use Illuminate\Support\Collection;

class AddRouteVisitor extends AbstractRouteVisitor
{
    private string $routeToAdd;
    private bool $groupFound = false;
    private bool $routeAdded = false;

    public function __construct(string $targetGroup, string $routeToAdd)
    {
        parent::__construct($targetGroup);
        $this->routeToAdd = $routeToAdd;
    }

    public function leaveNode(Node $node): ?Node
    {
        if ($node instanceof Node\Stmt\Return_ && $node->expr instanceof Array_) {
            foreach ($node->expr->items as $item) {
                if ($item instanceof ArrayItem && $this->isTargetGroup($item)) {
                    $this->groupFound = true;

                    if (!$item->value instanceof Array_) {
                        // If the group value is not an array, we cannot add to it.
                        // This might indicate a malformed config.
                        continue;
                    }

                    // Check if route already exists
                    $routeExists = false;
                    foreach ($item->value->items as $routeItem) {
                        $currentRouteValue = null;
                        if ($routeItem->value instanceof ClassConstFetch) {
                            // Handle ClassName::class format
                            if ($routeItem->value->class instanceof Name) {
                                $currentRouteValue = $routeItem->value->class->toString() . '::class';
                            }
                        } elseif ($routeItem->value instanceof String_) {
                            // Handle string format
                            $currentRouteValue = $routeItem->value->value;
                        }

                        if ($currentRouteValue === $this->routeToAdd) {
                            $routeExists = true;
                            break;
                        }
                    }

                    if (!$routeExists) {
                        // Parse the route string to add it properly
                        if (str_ends_with($this->routeToAdd, '::class')) {
                            // This is a class reference
                            $className = substr($this->routeToAdd, 0, -7);
                            $newRouteNode = new ClassConstFetch(
                                new Name($className),
                                'class'
                            );
                        } else {
                            // This is a string
                            $newRouteNode = new String_($this->routeToAdd);
                        }
                        $newRoute = new ArrayItem($newRouteNode);

                        $isDuplicate = collect($item->value->items)->contains(function ($value) use ($newRoute): bool {
                            if ($value->value instanceof ClassConstFetch && $newRoute->value instanceof ClassConstFetch) {
                                return ltrim($value->value->class->toString(), '\\') === ltrim($newRoute->value->class->toString(), '\\');
                            }
                            if ($value->value instanceof String_ && $newRoute->value instanceof String_) {
                                return ltrim($value->value->value, '\\') === ltrim($newRoute->value->value, '\\');
                            }
                            return false;
                        });

                        if (! $isDuplicate) {
                            $item->value->items[] = $newRoute;
                            $this->routeAdded = true;
                        }
                    }
                    // Stop searching once the target group is found and processed
                    break;
                }
            }
        }
        // Returning null is important in leaveNode if no modification is made to the node itself
        return null;
    }

    public function hasGroupFound(): bool
    {
        return $this->groupFound;
    }

    public function hasRouteAdded(): bool
    {
        return $this->routeAdded;
    }
}
