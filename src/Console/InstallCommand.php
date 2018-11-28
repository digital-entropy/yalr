<?php

namespace Jalameta\Router\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * Route Installer
 *
 * @author      veelasky <veelasky@gmail.com>
 */
class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jps-router:install {--remove}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install JPS router in favor of original laravel router';

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