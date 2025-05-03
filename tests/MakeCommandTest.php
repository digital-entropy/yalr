<?php

namespace Dentro\Yalr\Tests;

use Dentro\Yalr\Helpers\YalrConfig;
use Dentro\Yalr\Console\MakeCommand;
use Orchestra\Testbench\TestCase;
use Mockery;

class MakeCommandTest extends TestCase
{
    private $tempConfigFile;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a temporary file for testing
        $this->tempConfigFile = tempnam(sys_get_temp_dir(), 'yalrtest_');

        // Write some basic config content to the temporary file
        $initialContent = <<<'PHP'
<?php

return [
    'preloads' => [
        // preloads here
    ],
    'web' => [
        // web routes here
    ],
    'api' => [
        // api routes here
    ],
];
PHP;
        file_put_contents($this->tempConfigFile, $initialContent);

        // Set the config path for testing
        YalrConfig::setConfigPath($this->tempConfigFile);
    }

    protected function tearDown(): void
    {
        // Clean up the temporary file
        if (file_exists($this->tempConfigFile)) {
            unlink($this->tempConfigFile);
        }

        // Reset the config path
        YalrConfig::resetConfigPath();

        parent::tearDown();
    }

    public function testInjectRouteClass(): void
    {
        // Create a mock of the command
        $command = Mockery::mock(MakeCommand::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $command->shouldReceive('option')
            ->with('inject')
            ->andReturn('web');

        $command->shouldReceive('info')
            ->once()
            ->with(Mockery::pattern("/injected to `routes\.php` in `web` group/"));

        // Call the protected method
        $command->shouldAllowMockingProtectedMethods()
            ->injectRouteClass('App\Http\Routes\TestRoute');

        // Check that the class was actually added to the file
        $content = file_get_contents($this->tempConfigFile);
        $this->assertStringContainsString('App\Http\Routes\TestRoute::class', $content);
    }

    public function testInjectRouteClassFailsWithInvalidSection(): void
    {
        // Create a mock of the command with invalid section
        $command = Mockery::mock(MakeCommand::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $command->shouldReceive('option')
            ->with('inject')
            ->andReturn('non_existent_section');

        $command->shouldReceive('error')
            ->once()
            ->with(Mockery::pattern("/Failed injecting route/"));

        // Set an invalid path to force failure
        YalrConfig::setConfigPath('/non/existent/path.php');

        // Call the protected method
        $command->shouldAllowMockingProtectedMethods()
            ->injectRouteClass('App\Http\Routes\TestRoute');
    }

    protected function getPackageProviders($app)
    {
        // Comment out until we have an actual service provider to use
        return [
            'Dentro\Yalr\YalrServiceProvider',
        ];
    }
}
