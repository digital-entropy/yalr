<?php

namespace Dentro\Yalr\Console;

use Dentro\Yalr\Helpers\YalrConfig;
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
     */
    public function getDefaultNamespace($rootNameSpace): string
    {
        return $rootNameSpace.'\Http\Routes';
    }

    /**
     * Execute the console command.
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle(): int
    {
        parent::handle();

        return 0;
    }

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        $stub = $this->option('controller') ? '/../../stubs/route.controller.stub' : '/../../stubs/route.stub';

        return __DIR__.$stub;
    }

    /**
     * Build the class with the given name.
     *
     * @param  string $name
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
     */
    protected function buildController(): void
    {
        $this->call('make:controller', [
            'name' => str_replace($this->type, 'Controller', $this->getNameInput()),
        ]);
    }

    /**
     * Get Controller class name without namespace.
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
        $route_group = $this->option('inject');

        if (YalrConfig::add($route_group, "{$name}::class")) {
            $this->info("`{$name}` injected to `routes.php` in `{$route_group}` group.");
        } else {
            $this->error("Failed injecting route: file `routes.php` not found or group `{$route_group}` undefined");
        }
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        return [
            ['controller', 'c', InputOption::VALUE_NONE, 'Generate controller accompanying route class.'],
            ['inject', 'j', InputOption::VALUE_OPTIONAL, 'Automatically inject route into registered array.'],
        ];
    }
}
