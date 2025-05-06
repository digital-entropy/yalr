<?php

namespace Dentro\Yalr\Console;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Dentro\Yalr\Helpers\RouteTransformer;

class InstallCommand extends Command
{
    protected $signature = 'yalr:install
                            {--transform : Transform existing route files to Yalr format}
                            {--backup : Create backup of original route files}';

    protected $description = 'Manage YALR in favor of original laravel router';

    public function __construct(
        private Filesystem $filesystem
    )
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->comment('Publishing YALR config file...');

        if ($this->runningInLumen()) {
            if ($this->filesystem->exists(base_path('config')) === false) {
                $this->filesystem->makeDirectory(base_path('config'));
            }

            $this->filesystem->copy(__DIR__.'/../../config/routes.php', base_path('config/routes.php'));
        } else {
            $this->callSilent('vendor:publish', ['--tag' => 'yalr-config']);
        }

        if ($this->option('transform')) {
            $this->transformRouteFiles();
        }

        return 0;
    }

    /**
     * Transform traditional Laravel route files to Yalr format
     */
    protected function transformRouteFiles(): void
    {
        $routesPath = base_path('routes');

        if (!$this->filesystem->exists($routesPath)) {
            $this->warn('Routes directory not found. Skipping transformation.');
            return;
        }

        $this->info('Transforming route files...');

        $routeFiles = (new Finder())
            ->files()
            ->name('*.php')
            ->in($routesPath);

        if (!count($routeFiles)) {
            $this->warn('No route files found in routes directory.');
            return;
        }

        $transformer = new RouteTransformer();
        $routesTransformed = 0;

        foreach ($routeFiles as $file) {
            $filePath = $file->getRealPath();
            $relativePath = Str::after($filePath, base_path() . DIRECTORY_SEPARATOR);

            // Skip files that don't have Route::* declarations
            $content = $this->filesystem->get($filePath);
            if (!Str::contains($content, 'Route::')) {
                $this->line("Skipping {$relativePath} - no Laravel routes found");
                continue;
            }

            $this->line("Processing {$relativePath}...");

            // Create backup if requested
            if ($this->option('backup')) {
                $backupPath = $filePath . '.bak';
                $this->filesystem->copy($filePath, $backupPath);
                $this->line("  Created backup at {$backupPath}");
            }

            // Transform the file
            $className = $this->getRouteClassName($file->getFilenameWithoutExtension());
            $namespace = $this->getNamespace($filePath);

            $transformedContent = $transformer->transformRouteFile(
                $content,
                $className,
                $namespace
            );

            if ($transformedContent) {
                $transformedPath = $this->getTransformedPath($filePath, $className);
                $this->filesystem->put($transformedPath, $transformedContent);
                $this->info("  Created Yalr route file: {$transformedPath}");
                $routesTransformed++;
            } else {
                $this->warn("  Failed to transform {$relativePath}");
            }
        }

        $this->newLine();
        $this->info("Transformation complete: {$routesTransformed} route files processed");
        $this->comment('To register these routes, add them to your config/routes.php file in the appropriate section.');
    }

    /**
     * Generate a class name for the route file
     */
    protected function getRouteClassName(string $filename): string
    {
        return Str::studly($filename) . 'Route';
    }

    /**
     * Determine the namespace based on file location
     */
    protected function getNamespace(string $filePath): string
    {
        // Default namespace for route classes
        return 'App\\Http\\Routes';
    }

    /**
     * Get path for transformed route file
     */
    protected function getTransformedPath(string $originalPath, string $className): string
    {
        $directory = app_path('Http/Routes');

        // Create directory if it doesn't exist
        if (!$this->filesystem->exists($directory)) {
            $this->filesystem->makeDirectory($directory, 0755, true);
        }

        return $directory . '/' . $className . '.php';
    }

    protected function runningInLumen(): bool
    {
        return Str::contains(app()->version(), 'Lumen');
    }
}
