<?php

namespace Dentro\Yalr\Helpers;

/**
 * Helper class to parse PHP tokens and modify the configuration
 */
class ConfigTokenParser
{
    /**
     * @var array Token array from token_get_all
     */
    private array $tokens;

    /**
     * @var string Target section to modify
     */
    private string $section;

    /**
     * @var string Class to add to the section
     */
    private string $class;

    /**
     * @var string Accumulated output
     */
    private string $output = '';

    /**
     * @var bool Whether we've found the return statement
     */
    private bool $inReturnStatement = false;

    /**
     * @var bool Whether we're in the target section
     */
    private bool $inTargetSection = false;

    /**
     * @var bool Whether we've found our section
     */
    private bool $foundSection = false;

    /**
     * @var int Current bracket nesting level
     */
    private int $bracketLevel = 0;

    /**
     * @var string Current key being processed
     */
    private string $currentKey = '';

    /**
     * @var bool Whether we're expecting a key next
     */
    private bool $expectingKey = false;

    /**
     * Constructor
     *
     * @param array $tokens Token array
     * @param string $section Target section
     * @param string $class Class to add
     */
    public function __construct(array $tokens, string $section, string $class)
    {
        $this->tokens = $tokens;
        $this->section = $section;
        $this->class = $class;
    }

    /**
     * Process tokens and generate modified content
     *
     * @return string The modified content
     */
    public function process(): string
    {
        $this->parseTokens();

        // If section not found, add it to the config
        if (!$this->foundSection) {
            return $this->addNewSection();
        }

        return $this->output;
    }

    /**
     * Parse tokens and update content
     */
    private function parseTokens(): void
    {
        foreach ($this->tokens as $i => $token) {
            if (is_array($token)) {
                $this->handleArrayToken($token, $i);
            } else {
                $this->handleStringToken($token, $i);
            }
        }
    }

    /**
     * Handle array token (has token ID and text)
     *
     * @param array $token The token
     * @param int $position Current position in token array
     */
    private function handleArrayToken(array $token, int $position): void
    {
        [$id, $text] = $token;

        // Look for return statement
        if ($id === T_RETURN) {
            $this->inReturnStatement = true;
        }

        // Track array keys when expecting them
        if ($this->expectingKey && $id === T_CONSTANT_ENCAPSED_STRING) {
            $this->currentKey = trim($text, "'\"");
            $this->expectingKey = false;

            // Check if we found our target section
            if ($this->currentKey === $this->section) {
                $this->inTargetSection = true;
                $this->foundSection = true;
            }
        }

        $this->output .= $text;
    }

    /**
     * Handle string token (single character)
     *
     * @param string $token The token
     * @param int $position Current position in token array
     */
    private function handleStringToken(string $token, int $position): void
    {
        // Handle token based on what it is
        switch ($token) {
            case '=>':
                $this->expectingKey = false;
                break;

            case '[':
                $this->bracketLevel++;

                // If this is the opening bracket of our target section's array
                if ($this->inTargetSection && $this->bracketLevel === 1) {
                    $this->output .= $token;
                    $this->injectClass($position);
                    return; // Skip adding token again
                }
                break;

            case ']':
                $this->bracketLevel--;

                // End of the target section array
                if ($this->inTargetSection && $this->bracketLevel === 0) {
                    $this->inTargetSection = false;
                    $this->currentKey = '';
                }
                break;

            case ',':
                // After a comma at the root level of the return array, we expect a key
                if ($this->inReturnStatement && $this->bracketLevel === 0) {
                    $this->expectingKey = true;
                }
                break;
        }

        $this->output .= $token;
    }

    /**
     * Inject the class at the appropriate position
     *
     * @param int $position Current position in token array
     */
    private function injectClass(int $position): void
    {
        // Find the next significant token
        $nextTokens = array_slice($this->tokens, $position + 1);
        $foundComment = false;
        $foundContent = false;

        foreach ($nextTokens as $nextToken) {
            if (is_array($nextToken)) {
                [$nextId, $nextText] = $nextToken;

                // Skip whitespace
                if ($nextId === T_WHITESPACE) {
                    continue;
                }

                // If we find a comment, remember it
                if ($nextId === T_COMMENT || $nextId === T_DOC_COMMENT) {
                    $foundComment = true;
                } else {
                    $foundContent = true;
                    break;
                }
            } elseif ($nextToken === ']') {
                // If we reach closing bracket without content
                break;
            }
        }

        // Add the new class in the right spot
        if (!$foundContent || $foundComment) {
            $this->output .= PHP_EOL . '        ' . $this->class . ',';
        }
    }

    /**
     * Add a new section to the config file
     *
     * @return string Modified content with new section added
     */
    private function addNewSection(): string
    {
        // Find a good place to add our new section
        $pattern = '/return\s+\[\s+/s';
        $replacement = "return [\n    '{$this->section}' => [\n        {$this->class},\n    ],\n    ";

        return preg_replace($pattern, $replacement, $this->output);
    }
}
