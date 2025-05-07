<?php

namespace Dentro\Yalr\Tests;

use Dentro\Yalr\Helpers\YalrConfig;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class YalrConfigTest extends TestCase
{
    private $tempConfigFile;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a temporary file for testing
        $this->tempConfigFile = tempnam(sys_get_temp_dir(), 'yalrtest_');

        // Write a more realistic config content to the temporary file
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

    public function testAddToExistingSection(): void
    {
        // Act
        $result = YalrConfig::add('preloads', '\App\Http\TestPreload::class');

        // Assert
        $this->assertTrue($result);

        // Read the content after modification
        $content = file_get_contents($this->tempConfigFile);
        $this->assertStringContainsString('\App\Http\TestPreload::class', $content, 'Class should be added to the file');
    }

    public function testAddToWebSection(): void
    {
        // Act
        $result = YalrConfig::add('web', '\App\Http\Controllers\WebController::class');
        $content = file_get_contents($this->tempConfigFile);

        // Assert
        $this->assertTrue($result);
        $this->assertStringContainsString('\App\Http\Controllers\WebController::class', $content);
        $this->assertStringContainsString("'web' => [", $content);
    }

    public function testAddToApiSection(): void
    {
        // Act
        $result = YalrConfig::add('api', '\App\Http\Controllers\ApiController::class');
        $content = file_get_contents($this->tempConfigFile);

        // Assert
        $this->assertTrue($result);
        $this->assertStringContainsString('\App\Http\Controllers\ApiController::class', $content);
        $this->assertStringContainsString("'api' => [", $content);
    }

    public function testAddDuplicateDoesNotAddAgain(): void
    {
        // Arrange
        YalrConfig::add('preloads', '\App\Http\UniquePreload::class');
        $contentBefore = file_get_contents($this->tempConfigFile);
        $initialOccurrences = substr_count($contentBefore, '\App\Http\UniquePreload::class');

        // Act
        YalrConfig::add('preloads', '\App\Http\UniquePreload::class');
        $contentAfter = file_get_contents($this->tempConfigFile);
        $finalOccurrences = substr_count($contentAfter, '\App\Http\UniquePreload::class');

        // Assert
        $this->assertEquals($initialOccurrences, $finalOccurrences);
    }

    public function testAddToCustomConfig(): void
    {
        // Arrange
        $customConfigFile = tempnam(sys_get_temp_dir(), 'yalrcustom_');
        $initialContent = <<<'PHP'
<?php

return [
    'custom' => [
        // custom items here
    ],
];
PHP;
        file_put_contents($customConfigFile, $initialContent);

        // Act
        $result = YalrConfig::add('custom', '\App\Custom\Class::class', $customConfigFile);

        // Assert
        $this->assertTrue($result);

        // Read the content after modification
        $content = file_get_contents($customConfigFile);
        $this->assertStringContainsString('\App\Custom\Class::class', $content, 'Custom class should be added to the file');

        // Clean up
        unlink($customConfigFile);
    }

    public function testAddToNonExistentFile(): void
    {
        $path = '/non/existent/path.php';

        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('YalrConfig: Config file not found at '.$path);

        // Act
        YalrConfig::add('test', '\App\Test::class', $path);
    }

    public function testAddToNewSection(): void
    {
        // Act
        $result = YalrConfig::add('newSection', '\App\NewSection\Class::class');

        // Assert
        $this->assertTrue($result);

        // Read the content after modification
        $content = file_get_contents($this->tempConfigFile);
        $this->assertStringContainsString("'newSection' => [", $content, 'New section should be added');
        $this->assertStringContainsString('\App\NewSection\Class::class', $content, 'Class should be added to the new section');
    }

    public function testAddToSectionWithExistingContent(): void
    {
        // Arrange
        $file = tempnam(sys_get_temp_dir(), 'yalrexisting_');
        $initialContent = <<<'PHP'
<?php

return [
    'existing' => [
        \App\Existing\FirstClass::class,
    ],
];
PHP;
        file_put_contents($file, $initialContent);

        // Act
        $result = YalrConfig::add('existing', '\App\Existing\SecondClass::class', $file);

        // Assert
        $this->assertTrue($result);

        // Read the content after modification
        $content = file_get_contents($file);
        $this->assertStringContainsString('\App\Existing\FirstClass::class', $content);
        $this->assertStringContainsString('\App\Existing\SecondClass::class', $content);

        // Clean up
        unlink($file);
    }

    public function testGetConfigPath(): void
    {
        // Act
        $path = YalrConfig::getConfigPath();

        // Assert
        $this->assertEquals($this->tempConfigFile, $path);

        // Reset and check the default path
        YalrConfig::resetConfigPath();
        $defaultPath = YalrConfig::getConfigPath();

        // Should end with /config/routes.php
        $this->assertStringEndsWith('/config/routes.php', $defaultPath);
    }

    public function testComplexConfigStructure(): void
    {
        // Arrange
        $complexFile = tempnam(sys_get_temp_dir(), 'yalrcomplex_');
        $complexContent = <<<'PHP'
<?php

return [
    // Complex nested configuration
    'groups' => [
        'web' => [
            'middleware' => ['web', 'auth'],
            'prefix' => 'app',
        ],
        'api' => [
            'middleware' => 'api',
            'prefix' => 'api/v1',
        ],
    ],
    /*
     * Multi-line comment
     * in web section
     */
    'web' => [
        // Comment in web section
        \App\Http\Routes\DefaultRoute::class,
    ],
    'api' => [
        // API routes
    ],
];
PHP;
        file_put_contents($complexFile, $complexContent);

        // Act
        $result = YalrConfig::add('web', '\App\Http\Routes\NewRoute::class', $complexFile);

        // Assert
        $this->assertTrue($result);

        // Read the content after modification
        $content = file_get_contents($complexFile);
        $this->assertStringContainsString('\App\Http\Routes\DefaultRoute::class', $content);
        $this->assertStringContainsString('\App\Http\Routes\NewRoute::class', $content);

        // Make sure comments are preserved
        $this->assertStringContainsString('// Comment in web section', $content);
        $this->assertStringContainsString('Multi-line comment', $content);
        $this->assertStringContainsString('comment below class string', $content);

        // Clean up
        unlink($complexFile);
    }
}
