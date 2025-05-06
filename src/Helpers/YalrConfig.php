<?php

namespace Dentro\Yalr\Helpers;

use InvalidArgumentException;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use PhpParser\PrettyPrinter;

class YalrConfig
{
    /**
     * Custom config path for testing purposes
     */
    protected static ?string $configPath = null;

    /**
     * Default config file name
     */
    protected static string $configFileName = 'routes.php';

    /**
     * Add Route to config/routes.php
     */
    public static function add(string $group, string $route, ?string $customConfigPath = null): bool
    {
        if ($group === '' || $group === '0') {
            throw new InvalidArgumentException('Group name cannot be empty');
        }

        if ($route === '' || $route === '0') {
            throw new InvalidArgumentException('Route cannot be empty');
        }

        [$ast, $config_file, $parser] = self::getConfigFileAndAst($customConfigPath);

        if (!$ast || !$config_file || !$parser) {
            return false;
        }

        // Visitor to add a route to an existing group
        $addRouteVisitor = new class($group, $route) extends NodeVisitorAbstract {
            private bool $groupFound = false;
            private bool $routeAdded = false;

            public function __construct(private readonly string $targetGroup, private readonly string $routeToAdd)
            {
            }

            public function leaveNode(Node $node): null
            {
                if ($node instanceof Node\Stmt\Return_ && $node->expr instanceof Array_) {
                    foreach ($node->expr->items as $item) {
                        if ($item instanceof ArrayItem &&
                            $item->key instanceof String_ &&
                            $item->key->value === $this->targetGroup) {

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
        };

        $traverser = new NodeTraverser();
        $traverser->addVisitor($addRouteVisitor);
        $modifiedAst = $traverser->traverse($ast); // Use a new variable for the potentially modified AST

        if (!$addRouteVisitor->hasGroupFound()) {
            // Group isn't found, create it and add the route
            $addGroupVisitor = new class($group, $route) extends NodeVisitorAbstract {
                private bool $groupAdded = false;

                public function __construct(private readonly string $targetGroup, private readonly string $routeToAdd)
                {
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
            };

            $traverser = new NodeTraverser(); // Use a new traverser
            $traverser->addVisitor($addGroupVisitor);
            // Traverse the original AST again, or the result of the first traversal if it's safe
            $modifiedAst = $traverser->traverse($ast); // Re-traverse the original AST

            if (!$addGroupVisitor->hasGroupAdded()) {
                // Should not happen if AST structure is as expected, but good to check
                return false;
            }

            // Write the AST with the new group back to the file
            return self::writeAstToFile($modifiedAst, $config_file);
        }

        // If a group was found, check if the route was actually added (not a duplicate)
        if ($addRouteVisitor->hasRouteAdded()) {
            // Write the modified AST back to the file
            return self::writeAstToFile($modifiedAst, $config_file);
        }

        // Group found, but the route already existed
        return true;
    }


    /**
     * Remove a route from config/routes.php
     *
     * @throws InvalidArgumentException
     */
    public static function remove(string $group, string $route, ?string $customConfigPath = null): bool
    {
        if ($group === '' || $group === '0') {
            throw new InvalidArgumentException('Group name cannot be empty');
        }

        if ($route === '' || $route === '0') {
            throw new InvalidArgumentException('Route cannot be empty');
        }

        [$ast, $config_file, $parser] = self::getConfigFileAndAst($customConfigPath);

        if (!$ast || !$config_file || !$parser) {
            return false;
        }

        try {
            $removeVisitor = new class($group, $route) extends NodeVisitorAbstract {
                private bool $groupFound = false;
                private bool $routeRemoved = false;

                public function __construct(private readonly string $targetGroup, private readonly string $routeToRemove)
                {
                }

                public function leaveNode(Node $node): ?Node
                {
                    if ($node instanceof Node\Stmt\Return_ && $node->expr instanceof Array_) {
                        foreach ($node->expr->items as $item) {
                            if ($item instanceof ArrayItem &&
                                $item->key instanceof String_ &&
                                $item->key->value === $this->targetGroup) {

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
            };

            $traverser = new NodeTraverser();
            $traverser->addVisitor($removeVisitor);
            $modifiedAst = $traverser->traverse($ast);

            if (!$removeVisitor->hasGroupFound()) {
                // Group wasn't found, nothing to remove
                return false; // Or true, depending on desired behavior (idempotency)
            }

            if (!$removeVisitor->hasRouteRemoved()) {
                // Group found, but route was not in it
                return true; // Idempotent success
            }

            // Write the modified AST back to the file
            return self::writeAstToFile($modifiedAst, $config_file);

        } catch (Error) {
            // Log error
            return false;
        }
    }


    /**
     * Get routes in a specific group
     *
     * @throws InvalidArgumentException
     */
    public static function getRoutes(string $group, ?string $customConfigPath = null): array
    {
        if ($group === '' || $group === '0') {
            throw new InvalidArgumentException('Group name cannot be empty');
        }

        [$ast] = self::getConfigFileAndAst($customConfigPath);

        if (!$ast) {
            return [];
        }

        $routes = [];
        try {
            $getRoutesVisitor = new class($group, $routes) extends NodeVisitorAbstract {
                private array $routes;

                public function __construct(private readonly string $targetGroup, array &$routes)
                {
                    $this->routes = &$routes;
                }

                // Use enterNode for early exit once group is found and processed
                public function enterNode(Node $node)
                {
                    if ($node instanceof Node\Stmt\Return_ && $node->expr instanceof Array_) {
                        foreach ($node->expr->items as $item) {
                            if ($item instanceof ArrayItem &&
                                $item->key instanceof String_ &&
                                $item->key->value === $this->targetGroup) {

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
            };

            $traverser = new NodeTraverser();
            $traverser->addVisitor($getRoutesVisitor);
            $traverser->traverse($ast);

            return $routes;

        } catch (Error) {
            // Log error
            return [];
        }
    }


    /**
     * Get all available groups in the config
     */
    public static function getGroups(?string $customConfigPath = null): array
    {
        [$ast, $config_file, $parser] = self::getConfigFileAndAst($customConfigPath);

        if (!$ast) {
            return [];
        }

        $groups = [];
        try {
            $getGroupsVisitor = new class($groups) extends NodeVisitorAbstract {
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
            };

            $traverser = new NodeTraverser();
            $traverser->addVisitor($getGroupsVisitor);
            $traverser->traverse($ast);

            // Filter out 'groups' and 'preloads' if they are treated specially
            // return array_diff($groups, ['groups', 'preloads']);
            // Or just return all keys found if 'groups' and 'preloads' can also contain route lists
            return $groups;


        } catch (Error) {
            // Log error
            return [];
        }
    }

    /**
     * Gets config path with respect to test overrides
     */
    public static function getConfigPath(): string
    {
        return self::$configPath ?? config_path(self::$configFileName);
    }

    /**
     * Set custom config path (useful for testing)
     */
    public static function setConfigPath(string $path): void
    {
        self::$configPath = $path;
    }

    /**
     * Reset config path to default
     */
    public static function resetConfigPath(): void
    {
        self::$configPath = null;
    }


    /**
     * Helper to get config file path, content, and parsed AST.
     *
     * @return array{?array, ?string, ?Parser} Returns [AST, config_file_path, parser] or [null, null, null] on failure.
     */
    private static function getConfigFileAndAst(?string $customConfigPath = null): array
    {
        $config_file = $customConfigPath ?? self::getConfigPath();

        if (!file_exists($config_file)) {
            // Optionally log: error_log("YalrConfig: Config file not found at {$config_file}");
            return [null, null, null];
        }

        $content = file_get_contents($config_file);
        if ($content === false) {
            // Optionally log: error_log("YalrConfig: Could not read config file at {$config_file}");
            return [null, null, null];
        }

        try {
            // It's slightly more efficient to reuse the parser if making multiple calls,
            // but for static methods, creating it each time is simpler.
            $parser = (new ParserFactory)->createForVersion(PhpVersion::fromString('8.0.0'));
            $ast = $parser->parse($content); // Removed preserveComments for potentially cleaner output

            if (!$ast) {
                // Optionally log: error_log("YalrConfig: Failed to parse config file AST at {$config_file}");
                return [null, null, null];
            }

            return [$ast, $config_file, $parser];
        } catch (Error) {
            // Optionally log: error_log("YalrConfig: Parser error in {$config_file}: " . $error->getMessage());
            return [null, null, null];
        }
    }

    /**
     * Helper to write the modified AST back to the config file.
     *
     * @param array $ast The Abstract Syntax Tree.
     * @param string $filePath The path to write the file to.
     * @return bool Success or failure.
     */
    private static function writeAstToFile(array $ast, string $filePath): bool
    {
        try {
            $prettyPrinter = new PrettyPrinter\Standard();
            $newContent = $prettyPrinter->prettyPrintFile($ast);

            // Add a newline at the end if the printer doesn't
            if (!str_ends_with($newContent, "\n")) {
                $newContent .= "\n";
            }

            return (bool)file_put_contents($filePath, $newContent);
        } catch (\Exception) {
            // Log exception
            // error_log("YalrConfig: Failed to write modified AST to {$filePath}: " . $e->getMessage());
            return false;
        }
    }
}
