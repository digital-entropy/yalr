<?php

namespace Dentro\Yalr\Tests\Commands;

use Dentro\Yalr\Console\GenerateCommand;
use Dentro\Yalr\Helpers\ControllerScanner;
use Dentro\Yalr\Helpers\YalrConfig;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class YalrGenerateCommandTest extends TestCase
{
    private string|false $tempConfigFile;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a temporary file for testing
        $this->tempConfigFile = tempnam(sys_get_temp_dir(), 'yalrtest_').'.php';

        // Write a realistic config content with the new injects configuration
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

        Mockery::close();

        parent::tearDown();
    }

    public function testCommandScansAndInjectsControllers(): void
    {
        // Create a regular mock for the ControllerScanner
        $scannerMock = Mockery::mock(ControllerScanner::class);
        $scannerMock->shouldReceive('scan')
            ->with('app/Controllers/Web/')
            ->andReturn(['\\App\\Controllers\\Web\\HomeController::class', '\\App\\Controllers\\Web\\UserController::class']);
        $scannerMock->shouldReceive('scan')
            ->with('app/Controllers/Api/')
            ->andReturn(['\\App\\Controllers\\Api\\ApiController::class']);

        // Create the command with mock methods
        $command = $this->createPartialMock(GenerateCommand::class, [
            'getControllerScanner',
            'getConfigPath',
            'addToConfig'
        ]);

        $command->method('getControllerScanner')->willReturn($scannerMock);
        $command->method('getConfigPath')->willReturn($this->tempConfigFile);
        $command->method('addToConfig')->willReturn(true);

        // Set up Laravel-style console testing
        $output = new BufferedOutput();
        $input = new ArrayInput([]);

        // Get the command's console instance reflection
        $reflection = new \ReflectionClass($command);
        $outputProp = $reflection->getProperty('output');
        $outputProp->setAccessible(true);
        $outputProp->setValue($command, $output);

        $inputProp = $reflection->getProperty('input');
        $inputProp->setAccessible(true);
        $inputProp->setValue($command, $input);

        // Run the command
        $result = $command->handle();

        // Verify the output
        $outputContent = $output->fetch();

        // Check exit code
        $this->assertEquals(0, $result);

        // Check controller scanning messages
        $this->assertStringContainsString("Scanning directory for 'web' group", $outputContent);
        $this->assertStringContainsString("Scanning directory for 'api' group", $outputContent);

        // Check injection success messages
        $this->assertStringContainsString("Successfully injected", $outputContent);
    }

    public function testCommandHandlesMultipleDirectoriesPerGroup(): void
    {
        // Create a config with multiple directories per group
        $multiDirectoryConfig = tempnam(sys_get_temp_dir(), 'yalrtest_multi_').'.php';
        $configContent = <<<'PHP'
<?php
return [
    'injects' => [
        'web' => ['app/Controllers/Web/', 'app/Controllers/Admin/'],
        'api' => 'app/Controllers/Api/' // Test single string value
    ],
    'web' => [],
    'api' => []
];
PHP;
        file_put_contents($multiDirectoryConfig, $configContent);

        // Create a scanner mock that handles multiple directories
        $scannerMock = Mockery::mock(ControllerScanner::class);
        $scannerMock->shouldReceive('scan')
            ->with('app/Controllers/Web/')
            ->andReturn(['\\App\\Controllers\\Web\\HomeController::class']);
        $scannerMock->shouldReceive('scan')
            ->with('app/Controllers/Admin/')
            ->andReturn(['\\App\\Controllers\\Admin\\AdminController::class']);
        $scannerMock->shouldReceive('scan')
            ->with('app/Controllers/Api/')
            ->andReturn(['\\App\\Controllers\\Api\\ApiController::class']);

        // Create the command with mock methods
        $command = $this->createPartialMock(GenerateCommand::class, [
            'getControllerScanner',
            'getConfigPath',
            'addToConfig'
        ]);

        $command->method('getControllerScanner')->willReturn($scannerMock);
        $command->method('getConfigPath')->willReturn($multiDirectoryConfig);
        $command->method('addToConfig')->willReturn(true);

        // Set up output capture
        $output = new BufferedOutput();
        $reflection = new \ReflectionClass($command);
        $outputProp = $reflection->getProperty('output');
        $outputProp->setAccessible(true);
        $outputProp->setValue($command, $output);

        $inputProp = $reflection->getProperty('input');
        $inputProp->setAccessible(true);
        $inputProp->setValue($command, new ArrayInput([]));

        // Run the command
        $result = $command->handle();
        $outputContent = $output->fetch();

        // Verify it processed all directories
        $this->assertEquals(0, $result);
        $this->assertStringContainsString("app/Controllers/Web/", $outputContent);
        $this->assertStringContainsString("app/Controllers/Admin/", $outputContent);
        $this->assertStringContainsString("app/Controllers/Api/", $outputContent);

        // Clean up
        if (file_exists($multiDirectoryConfig)) {
            unlink($multiDirectoryConfig);
        }
    }
}
