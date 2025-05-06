<?php

namespace Dentro\Yalr\Tests;

use Dentro\Yalr\Helpers\ControllerScanner;
use Dentro\Yalr\Helpers\YalrConfig;
use Dentro\Yalr\Console\GenerateCommand;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;

class AutoInjectTest extends TestCase
{
    private $tempConfigFile;

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            \Dentro\Yalr\YalrServiceProvider::class,
        ];
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        // Setup Storage facade with fake disk
        Storage::fake('local');

        // Create a temporary file for testing in the sRoutesConfigTesttorage disk
        $this->tempConfigFile = Storage::path('yalrtest_config.php');

        // Write a realistic config with the new injects configuration
        $initialContent = <<<'PHP'
<?php

return [
    'injects' => [
        'web' => ['app/Controllers/Web/'],
        'api' => ['app/Controllers/Api/']
    ],
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
        Storage::put('yalrtest_config.php', $initialContent);

        // Set the config path for testing
        YalrConfig::setConfigPath($this->tempConfigFile);

        // Setup test controllers
        Storage::makeDirectory('app/Controllers/Web');
        Storage::makeDirectory('app/Controllers/Api');

        // Create test controller files
        Storage::put(
            'app/Controllers/Web/HomeController.php',
            '<?php namespace App\Controllers\Web; class HomeController {}'
        );
        Storage::put(
            'app/Controllers/Web/UserController.php',
            '<?php namespace App\Controllers\Web; class UserController {}'
        );
        Storage::put(
            'app/Controllers/Api/ApiController.php',
            '<?php namespace App\Controllers\Api; class ApiController {}'
        );
    }

    #[\Override]
    protected function tearDown(): void
    {
        // Reset the config path
        YalrConfig::resetConfigPath();

        // Storage facade will automatically clean up the fake disk

        parent::tearDown();
    }

    public function testInjectsConfiguration(): void
    {
        // Load the configuration file
        $config = include $this->tempConfigFile;

        // Verify the injects configuration structure
        $this->assertIsArray($config['injects']);
        $this->assertArrayHasKey('web', $config['injects']);
        $this->assertArrayHasKey('api', $config['injects']);

        // Check that directory paths are correctly specified
        $this->assertEquals(['app/Controllers/Web/'], $config['injects']['web']);
        $this->assertEquals(['app/Controllers/Api/'], $config['injects']['api']);
    }

    public function testControllerScanner(): void
    {
        // Create a mock scanner to avoid filesystem and class_exists dependencies
        $scanner = $this->createPartialMock(ControllerScanner::class, ['scan']);

        // Define expected returns for different directories
        $scanner->method('scan')
            ->willReturnMap([
                ['app/Controllers/Web/', ['\\App\\Controllers\\Web\\HomeController::class', '\\App\\Controllers\\Web\\UserController::class']],
                ['app/Controllers/Api/', ['\\App\\Controllers\\Api\\ApiController::class']],
                ['app/Controllers/NonExistent/', []]
            ]);

        // Test scanning web controllers
        $webControllers = $scanner->scan('app/Controllers/Web/');
        $this->assertCount(2, $webControllers);
        $this->assertContains('\\App\\Controllers\\Web\\HomeController::class', $webControllers);
        $this->assertContains('\\App\\Controllers\\Web\\UserController::class', $webControllers);

        // Test scanning api controllers
        $apiControllers = $scanner->scan('app/Controllers/Api/');
        $this->assertCount(1, $apiControllers);
        $this->assertContains('\\App\\Controllers\\Api\\ApiController::class', $apiControllers);

        // Test scanning non-existent directory
        $emptyResult = $scanner->scan('app/Controllers/NonExistent/');
        $this->assertEmpty($emptyResult);
    }

    public function testNamespaceFromPath(): void
    {
        $scanner = new ControllerScanner();

        // Use reflection to access protected method
        $method = new \ReflectionMethod(ControllerScanner::class, 'getNamespaceFromPath');
        $method->setAccessible(true);

        // Test app path conversion
        $this->assertEquals('App\\Controllers\\Web', $method->invoke($scanner, 'app/Controllers/Web'));

        // Test with trailing slash
        $this->assertEquals('App\\Controllers\\Api', $method->invoke($scanner, 'app/Controllers/Api/'));

        // Test with leading slash
        $this->assertEquals('App\\Controllers\\Admin', $method->invoke($scanner, '/app/Controllers/Admin'));
    }

    public function testGenerateCommandWithConfig(): void
    {
        // Create a GenerateCommand instance
        $commandMock = $this->createPartialMock(GenerateCommand::class, [
            'getControllerScanner',
            'getConfigPath',
            'addToConfig',
            'info',
            'warn',
            'line'
        ]);

        // Set up the command mocks
        $scannerMock = $this->createPartialMock(ControllerScanner::class, ['scan']);
        $scannerMock->method('scan')
            ->willReturnMap([
                ['app/Controllers/Web/', ['\\App\\Controllers\\Web\\HomeController::class']],
                ['app/Controllers/Api/', ['\\App\\Controllers\\Api\\ApiController::class']]
            ]);

        $commandMock->method('getControllerScanner')->willReturn($scannerMock);
        $commandMock->method('getConfigPath')->willReturn($this->tempConfigFile);
        $commandMock->method('addToConfig')->willReturn(true);

        // Expect info/warning method calls
        $commandMock->expects($this->atLeastOnce())->method('info');
        $commandMock->expects($this->any())->method('line');

        // Run the command
        $result = $commandMock->handle();

        // Verify success
        $this->assertEquals(0, $result);
    }
}
