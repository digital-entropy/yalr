<?php

namespace Jalameta\Router\Console;

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
    protected $description = 'Create a new JPS route';

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
    public function getDefaultNamespace($rootNameSpace)
    {
        return $rootNameSpace.'\Http\Routes';
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        $stub = null;

        if ($this->option('controller'))
        {
            $stub = '/../../stubs/route.controller.stub';
        } else {
            $stub = '/../../stubs/route.stub';
        }

        return __DIR__.$stub;
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        if ($this->option('controller')) {
            $this->buildController($name);

            return str_replace(['DummyController'], [$this->getControllerClass($name)], parent::buildClass($name));
        }

        return parent::buildClass($name);
    }

    /**
     * Build controller.
     *
     * @param $name
     */
    protected function buildController($name)
    {
        $this->call('make:controller', ['name' => $this->getControllerClass($name)]);
    }

    /**
     * Get Controller class name
     *
     * @param $name
     *
     * @return mixed
     */
    protected function getControllerClass($name)
    {
        return str_replace($this->type, 'Controller', $name);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['controller', 'c', InputOption::VALUE_NONE, 'Generate controller accompanying route class.'],
        ];
    }
}
