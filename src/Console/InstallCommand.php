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
    protected $signature = 'yalr:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage YALR in favor of original laravel router';

    /**
     * InstallCommand constructor.
     */
    public function __construct(/**
     * Laravel filesystem.
     */
    private Filesystem $filesystem)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
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

        return 0;
    }

    /**
     * Determine if application is running on lumen.
     */
    protected function runningInLumen(): bool
    {
        return Str::contains(app()->version(), 'Lumen');
    }
}
