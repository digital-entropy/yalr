<?php

namespace Dentro\Yalr\Helpers;

use RuntimeException;
use InvalidArgumentException;

class YalrConfig
{
    /**
     * @var string|null The path to the configuration file
     */
    protected static ?string $configPath = null;

    /**
     * @var string|null The default configuration path
     */
    protected static ?string $defaultConfigPath = null;

    /**
     * Add a class to a section in the config file
     *
     * @param string $section The section to add to ('preloads', 'web', 'api', etc.)
     * @param string $class The class name to add
     * @param string|null $configPath Optional path to the config file
     * @return bool True if successful
     * @throws RuntimeException If the file exists but can't be read or written
     * @throws InvalidArgumentException If the section or class name is invalid
     */
    public static function add(string $section, string $class, ?string $configPath = null): bool
    {
        // Validate inputs
        if (empty($section)) {
            throw new InvalidArgumentException('Section name cannot be empty');
        }

        if (empty($class)) {
            throw new InvalidArgumentException('Class name cannot be empty');
        }

        $configPath ??= self::getConfigPath();

        // Check if file exists
        if (!file_exists($configPath)) {
            return false;
        }

        // Read file content
        $content = @file_get_contents($configPath);
        if ($content === false) {
            throw new RuntimeException("Unable to read configuration file: {$configPath}");
        }

        if (empty($content)) {
            return false;
        }

        // Skip if class already exists
        if (str_contains($content, $class)) {
            return true;
        }

        // Process the content
        $newContent = self::modifyConfig($content, $section, $class);

        // Write back if changed
        if ($newContent !== $content) {
            $result = @file_put_contents($configPath, $newContent);
            if ($result === false) {
                throw new RuntimeException("Unable to write to configuration file: {$configPath}");
            }

            return true;
        }

        return true;
    }

    /**
     * Modify the configuration content with the new class
     *
     * @param string $content Original file content
     * @param string $section Target section
     * @param string $class Class to add
     * @return string Modified content
     */
    protected static function modifyConfig(string $content, string $section, string $class): string
    {
        // Parse the PHP tokens
        $tokens = token_get_all($content);

        // Use the token parser to build new content
        $parser = new ConfigTokenParser($tokens, $section, $class);
        return $parser->process();
    }

    /**
     * Get the path to the routes config file
     *
     * @return string The configuration file path
     */
    public static function getConfigPath(): string
    {
        return self::$configPath ?? self::getDefaultConfigPath();
    }

    /**
     * Get the default config path
     *
     * @return string The default configuration file path
     */
    protected static function getDefaultConfigPath(): string
    {
        if (self::$defaultConfigPath === null) {
            self::$defaultConfigPath = dirname(__DIR__) . '/config/routes.php';
        }

        return self::$defaultConfigPath;
    }

    /**
     * Override the default config path (useful for testing)
     *
     * @param string $path New configuration file path
     * @return void
     */
    public static function setConfigPath(string $path): void
    {
        self::$configPath = $path;
    }

    /**
     * Reset the config path to the default
     *
     * @return void
     */
    public static function resetConfigPath(): void
    {
        self::$configPath = null;
    }
}

