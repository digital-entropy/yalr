<?php

namespace Jalameta\Router\Console;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * Make Command
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
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/../../stubs/route.stub';
    }

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
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {

        if ($this->option('controller'))
            $this->buildController($name);
        
        return parent::buildClass($name);
    }

    /**
     * Build controller
     *
     * @param $name
     */
    protected function buildController($name)
    {
        $controller = str_replace($this->type, 'Controller', $name);

        $this->call('make:controller', ['name' => $controller]);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            [ 'controller', 'c', InputOption::VALUE_NONE, 'Generate controller accompanying route class.']
        ];
    }
}