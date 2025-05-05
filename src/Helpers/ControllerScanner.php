<?php

namespace Dentro\Yalr\Helpers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Finder\Finder;

class ControllerScanner
{
    /**
     * Scan a directory for controller classes
     *
     * @param string $directory Directory path relative to app base path
     * @return array List of controller class names
     */
    public function scan(string $directory): array
    {
        $basePath = app()->basePath();
        $fullPath = $basePath . '/' . ltrim($directory, '/');

        if (!File::isDirectory($fullPath)) {
            return [];
        }

        $namespace = $this->getNamespaceFromPath($directory);
        $controllers = [];

        $files = (new Finder())->files()->name('*.php')->in($fullPath);

        foreach ($files as $file) {
            $className = $file->getBasename('.php');
            $fullClassName = $namespace . '\\' . $className;

            // Make sure the class exists and is loadable
            if (class_exists($fullClassName)) {
                $controllers[] = '\\' . $fullClassName . '::class';
            }
        }

        return $controllers;
    }

    /**
     * Convert a directory path to a namespace
     *
     * @param string $path
     * @return string
     */
    protected function getNamespaceFromPath(string $path): string
    {
        $path = trim($path, '/');

        // Handle app/ directory specially
        if (Str::startsWith($path, 'app/')) {
            // Remove app/ and convert to App namespace
            $path = Str::replaceFirst('app/', 'App/', $path);
        }

        // Convert directory separators to namespace separators
        return str_replace('/', '\\', $path);
    }
}
