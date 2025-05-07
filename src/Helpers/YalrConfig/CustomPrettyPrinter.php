<?php

namespace Dentro\Yalr\Helpers\YalrConfig;

use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt\InlineHTML;
use PhpParser\PrettyPrinter\Standard;

class CustomPrettyPrinter extends Standard
{
    protected function pExpr_Array(Expr\Array_ $node): string
    {
        $syntax = $node->getAttribute('kind', Expr\Array_::KIND_LONG);
        if ($syntax === Expr\Array_::KIND_SHORT) {
            $start = '[';
            $end = ']';
        } else {
            $start = 'array(';
            $end = ')';
        }

        if (empty($node->items)) {
            return $start . $end;
        }

        $this->indent();
        $result = $start . $this->nl;
        $first = true;
        foreach ($node->items as $item) {
            if ($first) {
                $first = false;
            } else {
                $result .= ',' . $this->nl;
            }

            // First, handle comments attached to this item
            $comments = $item->getAttribute('comments', []);
            if ($comments) {
                foreach ($comments as $comment) {
                    $result .= $this->nl . $comment->getText();
                }
                $result .= $this->nl;
            }

            $result .= $this->p($item);
        }
        $this->outdent();
        $result .= $this->nl . $end;
        return $result;
    }

    /**
     * Print file with preserved comments
     */
    public function prettyPrintFile(array $stmts): string
    {
        if (!$stmts) {
            return "<?php" . $this->newline . $this->newline;
        }

        $p = "<?php" . $this->newline . $this->newline;
        foreach ($stmts as $stmt) {
            // First, print file-level comments
            if ($stmt->getAttribute('comments') !== null) {
                foreach ($stmt->getAttribute('comments') as $comment) {
                    $p .= $comment->getText() . "\n";
                }
            }
            $p .= $this->prettyPrint([$stmt]) . "\n";
        }

        if ($stmts[0] instanceof InlineHTML) {
            $p = preg_replace('/^<\?php\s+\?>\r?\n?/', '', $p);
        }
        if ($stmts[count($stmts) - 1] instanceof InlineHTML) {
            $p = preg_replace('/<\?php$/', '', rtrim($p));
        }

        return $p;
    }

    /**
     * Overridden to handle comments better
     */
    protected function pArrayItem(ArrayItem $node): string
    {
        $result = '';
        if ($node->key !== null) {
            $result .= $this->p($node->key) . ' => ';
        }

        return $result . $this->p($node->value);
    }
}
