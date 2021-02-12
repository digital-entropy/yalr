<?php

namespace Jalameta\Router\Tests\Controllers;

use Spatie\RouteAttributes\Attributes\Get;

class GetTestController
{
    #[Get('my-get-method')]
    public function myGetMethod()
    {
    }
}
