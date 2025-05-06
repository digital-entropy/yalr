<?php

namespace Dentro\Yalr\Tests;

use Dentro\Yalr\Console\DisplayCommand;
use Dentro\Yalr\YalrServiceProvider;
use Illuminate\Support\Facades\Artisan;
use Orchestra\Testbench\TestCase;
use Mockery;

class DisplayCommandTest extends TestCase
{
    public function testDisplayCommand(): void
    {
        // Mock the app('yalr') service
        $mockYalr = Mockery::mock('yalr');
        $mockYalr->shouldReceive('all')
            ->once()
            ->andReturn([
                'web' => [
                    '\App\Http\Routes\WebRoutes',
                    '\App\Http\Routes\AuthRoutes',
                ],
                'api' => [
                    '\App\Http\Routes\ApiRoutes',
                ]
            ]);

        $this->app->instance('yalr', $mockYalr);

        // Run the command and test the output
        $this->artisan('yalr:display')
            ->expectsOutputToContain('WebRoutes')
            ->assertExitCode(0);
    }

    protected function getPackageProviders($app): array
    {
        return [
            YalrServiceProvider::class,
        ];
    }
}
