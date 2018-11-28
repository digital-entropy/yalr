<?php

namespace Jalameta\Router\Console;

use Illuminate\Console\GeneratorCommand;

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
}