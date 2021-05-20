<?php

namespace Dentro\Yalr\Tests\Controllers;

use Dentro\Yalr\Attributes\Get;
use Dentro\Yalr\Attributes\Put;
use Dentro\Yalr\Attributes\Name;
use Dentro\Yalr\Attributes\Post;
use Dentro\Yalr\Attributes\Delete;
use Dentro\Yalr\Attributes\Prefix;

#[Name('test', dotSuffix: true), Prefix('test')]
class GetTestController
{
    #[Get('/', name: 'index')]
    public function index(): void
    {
        //
    }

    #[Post('/', name: 'store')]
    public function store(): void
    {
        //
    }

    #[Get('/{test}', name: 'edit')]
    public function edit($test): void
    {
        //
    }

    #[Put('/{test}', name: 'update')]
    public function update($test): void
    {
        //
    }

    #[Delete('/{test}', name: 'destroy')]
    public function destroy(): void
    {
        //
    }
}
