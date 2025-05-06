<?php

namespace Dentro\Yalr\Console;

use Dentro\Yalr\Helpers\YalrConfig;
use Dentro\Yalr\Helpers\ControllerScanner;
use Illuminate\Console\Command;

class GenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'yalr:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan and inject controller classes into route groups based on injects configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Scanning routes configuration for controller directories...');

        // Get routes config
        $configPath = $this->getConfigPath();
        $config = include $configPath;

        if (!is_array($config)) {
            $this->error('Invalid routes configuration format!');
            return 1;
        }

        // Check if injects configuration exists
        if (!isset($config['injects']) || !is_array($config['injects'])) {
            $this->warn('No injects configuration found in routes config.');
            return 1;
        }

        $injectedRoutes = 0;
        $scanner = $this->getControllerScanner();

        // Process each inject configuration
        foreach ($config['injects'] as $routeGroup => $directories) {
            if (!is_array($directories)) {
                $directories = [$directories];
            }

            foreach ($directories as $directory) {
                $this->info("Scanning directory for '{$routeGroup}' group: {$directory}");

                // Ensure directory has trailing slash
                $directory = rtrim($directory, '/') . '/';

                // Scan the directory for controller classes
                $controllers = $scanner->scan($directory);

                if (empty($controllers)) {
                    $this->warn("No controller classes found in {$directory}");
                    continue;
                }

                $this->info("Found " . count($controllers) . " controller class(es) in {$directory}");

                // Add each controller to the config
                foreach ($controllers as $controller) {
                    $result = $this->addToConfig($routeGroup, $controller);
                    if ($result) {
                        $this->line("Added {$controller} to '{$routeGroup}' group");
                        $injectedRoutes++;
                    }
                }
            }
        }

        if ($injectedRoutes > 0) {
            $this->info("Successfully injected {$injectedRoutes} controller(s) into route groups.");
        } else {
            $this->info("No controllers were injected. Check your directory paths in the 'injects' configuration.");
        }

        return 0;
    }

    /**
     * Get the controller scanner instance.
     *
     * @return \Dentro\Yalr\Helpers\ControllerScanner
     */
    protected function getControllerScanner(): ControllerScanner
    {
        return new ControllerScanner();
    }

    /**
     * Get the configuration path.
     *
     * @return string
     */
    protected function getConfigPath(): string
    {
        return YalrConfig::getConfigPath();
    }

    /**
     * Add a controller to the configuration.
     *
     * @param string $key
     * @param string $controller
     * @return bool
     */
    protected function addToConfig(string $key, string $controller): bool
    {
        return YalrConfig::add($key, $controller);
    }
}
