<?php

namespace Dentro\Yalr\Helpers\YalrConfig;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

class GetGroupsVisitor extends NodeVisitorAbstract
{
    private array $groups;

    public function __construct(array &$groups)
    {
        $this->groups = &$groups;
    }

    // enterNode is suitable here as we only need the top-level keys
    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Return_ && $node->expr instanceof Array_) {
            foreach ($node->expr->items as $item) {
                // Ensure it's an item with a string key (representing a group)
                if ($item instanceof ArrayItem && $item->key instanceof String_) {
                    $this->groups[] = $item->key->value;
                }
            }
            // Found the main return array, no need to go deeper for group names
            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }
        return null; // Continue traversal if not the target node
    }
}
