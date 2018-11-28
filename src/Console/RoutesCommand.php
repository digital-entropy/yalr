<?php

namespace Jalameta\Router\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * Route Installer
 *
 * @author      veelasky <veelasky@gmail.com>
 */
class RoutesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jps:routes {--install} {--remove}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage JPS router in favor of original laravel router';

    /**
     * Laravel filesystem
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $filesystem;

    /**
     * InstallCommand constructor.
     *
     * @param \Illuminate\Filesystem\Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        parent::__construct();

        $this->filesystem = $filesystem;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($this->wantInstall())
        {
            $this->install();
        } else {
            $this->displayRoutes();
        }
    }

    /**
     * Display all registered routes in JPS container
     *
     * @return void
     */
    protected function displayRoutes()
    {
        $routes = app('jps.router')->all();
        $rows = [];

        foreach ($routes as $group => $classes)
        {
            foreach ($classes as $class)
            {
                $rows[] = [
                    $class, $group
                ];
            }
        }

        $this->table(['Route Class', 'Group'], $rows);
    }

    /**
     * Install JPS router
     *
     * @return void
     */
    protected function install()
    {
        $this->comment("Publishing JPS Router config file..");
        $this->callSilent("vendor:publish", [ "--tag" => 'jps-router-config']);

        if (
            is_null($this->option('remove')) === false
            AND $this->confirm("WARNING: Are you sure you want to remove default laravel route file?")
        ) {
            $this->removeLaravelRoute();
        }
    }

    /**
     * Determine if user want to install the package
     *
     * @return bool
     */
    protected function wantInstall()
    {
        return is_null($this->option('install')) === false;
    }

    /**
     * Remove laravel default route
     *
     * @return void
     */
    protected function removeLaravelRoute()
    {
        $this->filesystem->deleteDirectory(base_path('routes'));
        $this->filesystem->delete(app_path('Providers/RouteServiceProvider.php'));

        // remove explicit `require` in `app/Console/Kernel.php`
        $stream =  preg_replace(
            '/require base_path\(\'routes\/console\.php\'\);/m',
            '\/\/ require base_path(\'routes/console.php\');',
            file_get_contents(app_path('Console/Kernel.php'))
        );
        file_put_contents(app_path('Console/Kernel.php'), $stream);

        // and once more in `app/Providers/BroadcastServiceProvider.php`
        $stream =  preg_replace(
            '/require base_path\(\'routes\/channels\.php\'\);/m',
            '\/\/ require base_path(\'routes/channels.php\');',
            file_get_contents(app_path('Providers/BroadcastServiceProvider.php'))
        );
        file_put_contents(app_path('Providers/BroadcastServiceProvider.php'), $stream);
    }
}