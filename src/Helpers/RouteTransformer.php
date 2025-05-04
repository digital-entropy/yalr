<?php

namespace Dentro\Yalr\Helpers;

use Illuminate\Support\Str;
use Illuminate\Support\Collection; // Keep this use statement

class RouteTransformer
{
    /**
     * Transform a Laravel route file to Yalr format (Simplified)
     */
    public function transformRouteFile(string $content, string $className, string $namespace, string $prefix = '/'): ?string
    {
        // Simple check if there are any Route:: calls
        if (!Str::contains($content, 'Route::')) {
            return null;
        }

        // Extract use statements
        preg_match_all('/^use\s+[^;]+;/m', $content, $matches);
        $useStatements = implode("\n", $matches[0] ?? []);

        // Perform simple string replacement
        $modifiedContent = str_replace('Route::', '$this->router->', $content);

        // Remove the opening <?php tag and use statements from the main body
        $modifiedContent = ltrim($modifiedContent);
        if (Str::startsWith($modifiedContent, '<?php')) {
            $modifiedContent = Str::after($modifiedContent, '<?php');
        }
        // Remove the extracted use statements from the body content to avoid duplication
        $modifiedContent = preg_replace('/^use\s+[^;]+;/m', '', $modifiedContent);
        $modifiedContent = ltrim($modifiedContent); // Remove leading whitespace/newlines

        // Generate Yalr route class file
        return $this->generateRouteClass($modifiedContent, $className, $namespace, $prefix, $useStatements);
    }

    /**
     * Generate a Yalr route class from the modified content
     */
    protected function generateRouteClass(string $registerMethodBody, string $className, string $namespace, string $prefix = '/', string $useStatements = ''): string
    {
        $prefixProperty = '';
        if ($prefix !== '/') {
            $prefixProperty = <<<PHP

    /**
     * Route path prefix.
     */
    protected string \$prefix = '{$prefix}';

PHP;
        }

        // Add a newline after use statements if they exist
        $useStatementsBlock = '';
        if (!empty($useStatements)) {
            $useStatementsBlock = $useStatements . "\n";
        }

        // Indent the register method body
        $indentedBody = collect(explode("\n", trim($registerMethodBody)))
            ->map(fn ($line) => rtrim('        ' . $line)) // Add 8 spaces for indentation, trim trailing whitespace
            ->implode("\n");

        // Ensure the template has correct base indentation
        // Added $useStatementsBlock after namespace
        $template = <<<PHP
<?php

namespace {$namespace};

{$useStatementsBlock}
use Dentro\Yalr\BaseRoute;
// Note: We keep Route and Inertia facades here for convenience,
// even if they were in the original $useStatements.
// A more robust solution might de-duplicate them.
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Inertia;

class {$className} extends BaseRoute
{{$prefixProperty}
    /**
     * Register routes handled by this class
     */
    public function register(): void
    {
{$indentedBody}
    }
}
PHP;

        return $template;
    }
}
