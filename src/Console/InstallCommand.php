<?php

namespace Dentro\Yalr\Console;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * Route Installer.
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
    protected $signature = 'yalr:install {--remove : Remove default laravel routes installation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage YALR in favor of original laravel router';

    /**
     * Laravel filesystem.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    private Filesystem $filesystem;

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
     * @return int
     */
    public function handle(): int
    {
        $this->comment('Publishing YALR config file..');

        if ($this->runningInLumen()) {
            if ($this->filesystem->exists(base_path('config')) === false) {
                $this->filesystem->makeDirectory(base_path('config'));
            }

            $this->filesystem->copy(__DIR__.'/../../config/routes.php', base_path('config/routes.php'));
        } else {
            $this->callSilent('vendor:publish', ['--tag' => 'yalr-config']);
        }

        if (
            $this->option('remove')
            && $this->confirm('WARNING: Are you sure you want to remove default laravel route file?')
        ) {
            $this->removeLaravelRoute();
        }

        return 0;
    }

    /**
     * Determine if application is running on lumen.
     *
     * @return bool
     */
    protected function runningInLumen(): bool
    {
        return Str::contains(app()->version(), 'Lumen');
    }

    /**
     * Remove laravel default route.
     *
     * @return void
     */
    protected function removeLaravelRoute(): void
    {
        $this->filesystem->deleteDirectory(base_path('routes'));
        $this->comment('`routes` directory has been deleted!');

        if ($this->filesystem->exists(base_path('app/Providers/RouteServiceProvider.php'))) {
            $this->filesystem->delete(base_path('app/Providers/RouteServiceProvider.php'));
            $this->comment('`app/Providers/RouteServiceProvider.php` file has been deleted!');
        }

        // remove explicit `require` in `app/Console/Kernel.php`
        $stream = preg_replace(
            '/require base_path\(\'routes\/console\.php\'\);/m',
            '// require base_path(\'routes/console.php\');',
            file_get_contents(base_path('app/Console/Kernel.php'))
        );
        file_put_contents(base_path('app/Console/Kernel.php'), $stream);
        $this->comment('`app/Console/Kernel.php` has been modified!');

        // remove RouteServiceProvider from config/app.php
        if ($this->filesystem->exists(base_path('config/app.php'))) {
            $stream = preg_replace(
                '/App\\\Providers\\\RouteServiceProvider::class,/u',
                '// App\\\Providers\\\RouteServiceProvider::class,',
                file_get_contents(base_path('config/app.php'))
            );
            file_put_contents(base_path('config/app.php'), $stream);
            $this->comment('`config/app.php` has been modified!');
        }

        // and once more in `app/Providers/BroadcastServiceProvider.php`
        if ($this->filesystem->exists(base_path('app/Providers/BroadcastServiceProvider.php'))) {
            $stream = preg_replace(
                '/require base_path\(\'routes\/channels\.php\'\);/m',
                '// require base_path(\'routes/channels.php\');',
                file_get_contents(base_path('app/Providers/BroadcastServiceProvider.php'))
            );
            file_put_contents(base_path('app/Providers/BroadcastServiceProvider.php'), $stream);
            $this->comment('`app/Providers/BroadcastServiceProvider.php` has been modified!');
        }
    }
}
