<?php

namespace Dentro\Yalr\Helpers;

use InvalidArgumentException;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;

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
     * @throws \Exception
     */
    public static function add(string $group, string $route, ?string $customConfigPath = null): bool
    {
        [$ast, $config_file] = self::getConfigFileAndAst($customConfigPath);

        // Visitor to add a route to an existing group
        $addRouteVisitor = new YalrConfig\AddRouteVisitor($group, $route);

        $traverser = new NodeTraverser();
        $traverser->addVisitor($addRouteVisitor);
        $modifiedAst = $traverser->traverse($ast); // Use a new variable for the potentially modified AST

        if (!$addRouteVisitor->hasGroupFound()) {
            // Group isn't found, create it and add the route
            $addGroupVisitor = new YalrConfig\AddGroupVisitor($group, $route);

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
     * @throws \Exception
     */
    public static function remove(string $group, string $route, ?string $customConfigPath = null): bool
    {
        [$ast, $config_file, $parser] = self::getConfigFileAndAst($customConfigPath);

        if (!$ast || !$config_file || !$parser) {
            return false;
        }

        $removeVisitor = new YalrConfig\RemoveRouteVisitor($group, $route);

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
        $getRoutesVisitor = new YalrConfig\GetRoutesVisitor($group, $routes);

        $traverser = new NodeTraverser();
        $traverser->addVisitor($getRoutesVisitor);
        $traverser->traverse($ast);

        return $routes;
    }


    /**
     * Get all available groups in the config
     */
    public static function getGroups(?string $customConfigPath = null): array
    {
        [$ast] = self::getConfigFileAndAst($customConfigPath);

        if (!$ast) {
            return [];
        }

        $groups = [];
        $getGroupsVisitor = new YalrConfig\GetGroupsVisitor($groups);

        $traverser = new NodeTraverser();
        $traverser->addVisitor($getGroupsVisitor);
        $traverser->traverse($ast);

        // Filter out 'groups' and 'preloads' if they are treated specially
        // return array_diff($groups, ['groups', 'preloads']);
        // Or just return all keys found if 'groups' and 'preloads' can also contain route lists
        return $groups;
    }

    /**
     * Gets config path with respect to test overrides
     */
    public static function getConfigPath(): string
    {
        return self::$configPath ?? config_path(self::$configFileName);
    }

    /**
     * Set a custom config path (useful for testing)
     */
    public static function setConfigPath(string $path): void
    {
        self::$configPath = $path;
    }

    /**
     * Reset a config path by default
     */
    public static function resetConfigPath(): void
    {
        self::$configPath = null;
    }


    /**
     * Helper to get a config file path, content, and parsed AST.
     *
     * @return array{?array, ?string, ?Parser} Returns [AST, config_file_path, parser].
     * @throws \RuntimeException When a config file isn't found or can't be read.
     * @throws Error When there's a parsing error.
     */
    private static function getConfigFileAndAst(?string $customConfigPath = null): array
    {
        $config_file = $customConfigPath ?? self::getConfigPath();

        if (!file_exists($config_file)) {
            throw new \RuntimeException("YalrConfig: Config file not found at $config_file");
        }

        $content = file_get_contents($config_file);
        if ($content === false) {
            throw new \RuntimeException("YalrConfig: Could not read config file at $config_file");
        }

        $parserFactory = new ParserFactory();
        $parser = $parserFactory->createForVersion(PhpVersion::fromString('8.3.0'));

        $ast = $parser->parse($content);

        if (!$ast) {
            throw new \RuntimeException("YalrConfig: Failed to parse config file AST at {$config_file}");
        }

        return [$ast, $config_file, $parser];
    }

    /**
     * Helper to write the modified AST back to the config file.
     *
     * @param array $ast The Abstract Syntax Tree.
     * @param string $filePath The path to write the file to.
     * @return bool Success or failure.
     * @throws \Exception When there's an error writing to the file.
     */
    private static function writeAstToFile(array $ast, string $filePath): bool
    {
        $prettyPrinter = new YalrConfig\CustomPrettyPrinter();

        $newContent = $prettyPrinter->prettyPrintFile($ast);
        $result = file_put_contents($filePath, $newContent);

        if ($result === false) {
            throw new \RuntimeException("YalrConfig: Failed to write modified AST to {$filePath}");
        }

        return true;
    }
}
