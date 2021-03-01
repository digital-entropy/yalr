<?php

namespace Jalameta\Router\Tests\Controllers;

use Jalameta\Router\Attributes\Get;
use Jalameta\Router\Attributes\Put;
use Jalameta\Router\Attributes\Name;
use Jalameta\Router\Attributes\Post;
use Jalameta\Router\Attributes\Delete;
use Jalameta\Router\Attributes\Prefix;

#[Name('test', dotSuffix: true), Prefix('test')]
class GetTestController
{
    #[Get('/', name: 'index')]
    public function index()
    {
        //
    }

    #[Post('/', name: 'store')]
    public function store()
    {
        //
    }

    #[Get('/{test}', name: 'edit')]
    public function edit($test)
    {
        //
    }

    #[Put('/{test}', name: 'update')]
    public function update($test)
    {
        //
    }

    #[Delete('/{test}', name: 'destroy')]
    public function destroy()
    {
        //
    }
}
