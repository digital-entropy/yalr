<?php

namespace Dentro\Yalr\Console;

use Dentro\Yalr\Helpers\YalrConfig;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class MakeCommand extends GeneratorCommand
{
    protected $name = 'make:route';

    protected $description = 'Create a new YALR';

    protected $type = 'Route';

    public function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Http\Routes';
    }

    public function handle(): int
    {
        parent::handle();

        return 0;
    }

    protected function getStub(): string
    {
        $stub = $this->option('controller') ? '/../../stubs/route.controller.stub' : '/../../stubs/route.stub';

        return __DIR__.$stub;
    }

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

    protected function buildController(): void
    {
        $this->call('make:controller', [
            'name' => str_replace($this->type, 'Controller', $this->getNameInput()),
        ]);
    }

    protected function getControllerClassname(): string
    {
        return str_replace(array($this->getNamespace($this->getNameInput()) . '\\', $this->type), array('', 'Controller'), $this->getNameInput());
    }

    protected function injectRouteClass($name): void
    {
        $route_group = $this->option('inject');

        if (YalrConfig::add($route_group, "{$name}::class")) {
            $this->info("`{$name}` injected to `routes.php` in `{$route_group}` group.");
        } else {
            $this->error("Failed injecting route: file `routes.php` not found or group `{$route_group}` undefined");
        }
    }

    protected function getOptions(): array
    {
        return [
            ['controller', 'c', InputOption::VALUE_NONE, 'Generate controller accompanying route class.'],
            ['inject', 'j', InputOption::VALUE_OPTIONAL, 'Automatically inject route into registered array.'],
        ];
    }
}
