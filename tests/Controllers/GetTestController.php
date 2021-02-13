<?php

namespace Jalameta\Router\Tests\Controllers;

use Jalameta\Router\Attributes\Get;
use Jalameta\Router\Attributes\Name;
use Jalameta\Router\Attributes\Prefix;

#[Name('test', dotSuffix: true), Prefix('test')]
class GetTestController
{
    #[Get('/', name: 'index')]
    public function index()
    {
    }
}
