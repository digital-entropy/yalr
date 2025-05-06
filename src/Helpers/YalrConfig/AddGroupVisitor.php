<?php

namespace Dentro\Yalr\Helpers\YalrConfig;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;

class AddGroupVisitor extends NodeVisitorAbstract
{
    private bool $groupAdded = false;
    private string $targetGroup;
    private string $routeToAdd;

    public function __construct(string $targetGroup, string $routeToAdd)
    {
        $this->targetGroup = $targetGroup;
        $this->routeToAdd = $routeToAdd;
    }

    public function leaveNode(Node $node): ?Node\Stmt\Return_
    {
        if ($node instanceof Node\Stmt\Return_ && $node->expr instanceof Array_) {
            // Create the value node for the route
            $routeValueNode = str_ends_with($this->routeToAdd, '::class')
                ? new ClassConstFetch(
                    new Name(substr($this->routeToAdd, 0, -7)),
                    'class'
                )
                : new String_($this->routeToAdd);
            // Create the array item for the route within the new group
            $routeArrayItem = new ArrayItem($routeValueNode);
            // Create the array for the new group
            $groupArray = new Array_([$routeArrayItem]);
            // Set attributes to match formatting if possible (optional)
            $groupArray->setAttribute('kind', Array_::KIND_SHORT);
            // Use [] syntax
            // Create the array item for the new group itself
            $newGroupItem = new ArrayItem(
                $groupArray,
                new String_($this->targetGroup) // Group name as key
            );
            // Add the new group item to the main return array
            $node->expr->items[] = $newGroupItem;
            $this->groupAdded = true;
            // No need to traverse further down from here for this visitor's purpose
            return $node;
        }
        return null; // Important for visitors
    }

    public function hasGroupAdded(): bool
    {
        return $this->groupAdded;
    }
}
