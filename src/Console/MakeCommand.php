<?php

namespace Dentro\Yalr\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * Make Command.
 *
 * @author      veelasky <veelasky@gmail.com>
 */
class MakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:route';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new YALR';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Route';

    /**
     * Get the default namespace for the class.
     *
     * @param $rootNameSpace
     *
     * @return string
     */
    public function getDefaultNamespace($rootNameSpace): string
    {
        return $rootNameSpace.'\Http\Routes';
    }

    /**
     * Execute the console command.
     *
     * @return int
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle(): int
    {
        parent::handle();

        return 0;
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub(): string
    {
        if ($this->option('controller')) {
            $stub = '/../../stubs/route.controller.stub';
        } else {
            $stub = '/../../stubs/route.stub';
        }

        return __DIR__.$stub;
    }

    /**
     * Build the class with the given name.
     *
     * @param  string $name
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass($name): string
    {
        if ($this->option('inject') !== null) {
            $this->injectRouteClass($name);
        }

        if ($this->option('controller')) {
            $this->buildController();

            return str_replace(
                [
                    'DummyRootNamespace',
                    'DummyControllerName',
                    'DummyController',
                ], [
                    $this->rootNamespace(),
                    str_replace($this->type, 'Controller', $this->getNameInput()),
                    $this->getControllerClassname(),
                ], parent::buildClass($name)
            );
        }

        return parent::buildClass($name);
    }

    /**
     * Generate new controller class.
     *
     * @return void
     */
    protected function buildController(): void
    {
        $this->call('make:controller', [
            'name' => str_replace($this->type, 'Controller', $this->getNameInput()),
        ]);
    }

    /**
     * Get Controller class name without namespace.
     *
     * @return string
     */
    protected function getControllerClassname(): string
    {
        return str_replace(array($this->getNamespace($this->getNameInput()) . '\\', $this->type), array('', 'Controller'), $this->getNameInput());
    }

    /**
     * Inject Route to `routes.php`.
     *
     * @param $name
     */
    protected function injectRouteClass($name): void
    {
        /** @var $filesystem Filesystem */
        $filesystem = app(Filesystem::class);
        $path = config_path('routes.php');
        $route_group = $this->option('inject');

        if (
            $filesystem->exists($path) &&
            preg_match('/\/\*\* \@inject '.$route_group.' \*\*\//', file_get_contents($path))
        ) {
            $stream = preg_replace(
                '/\/\*\* \@inject '.$route_group.' \*\*\//',
                "{$name}::class,"."\n".'        /** @inject '.$route_group.' **/',
                file_get_contents($path)
            );

            file_put_contents($path, $stream);

            $this->info("`{$name}` injected to `routes.php` in `{$route_group}` group.");
        } else {
            $this->error("Failed injecting route: file `routes.php` not found or group `{$route_group}` undefined");
        }
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['controller', 'c', InputOption::VALUE_NONE, 'Generate controller accompanying route class.'],
            ['inject', 'j', InputOption::VALUE_OPTIONAL, 'Automatically inject route into registered array.'],
        ];
    }
}
