<?php

namespace Dentro\Yalr\Tests\Helpers;

use Dentro\Yalr\Helpers\RouteTransformer;
use PHPUnit\Framework\TestCase;

class RouteTransformerTest extends TestCase
{
    protected RouteTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transformer = new RouteTransformer();
    }

    public function testTransformBasicRoutes(): void
    {
        $originalContent = <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Inertia;
use App\Http\Middleware\SomeMiddleware; // Add another use statement for testing

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified', SomeMiddleware::class])->name('dashboard');
PHP;

        $transformedContent = $this->transformer->transformRouteFile(
            $originalContent,
            'WebRoute',
            'App\\Http\\Routes'
        );

        $this->assertNotNull($transformedContent);
        $this->assertStringContainsString('namespace App\\Http\\Routes;', $transformedContent);
        // Check original use statements are present
        $this->assertStringContainsString('use Illuminate\Support\Facades\Route;', $transformedContent);
        $this->assertStringContainsString('use Illuminate\Support\Facades\Inertia;', $transformedContent);
        $this->assertStringContainsString('use App\Http\Middleware\SomeMiddleware;', $transformedContent);
        // Check standard use statements are present
        $this->assertStringContainsString('use Dentro\Yalr\BaseRoute;', $transformedContent);
        $this->assertStringContainsString('class WebRoute extends BaseRoute', $transformedContent);
        // Check for the replaced string
        $this->assertStringContainsString("\$this->router->get('/', function () {", $transformedContent);
        $this->assertStringContainsString("\$this->router->get('dashboard', function () {", $transformedContent);
        // Check if original chaining is preserved
        $this->assertStringContainsString("})->name('home');", $transformedContent);
        // Ensure SomeMiddleware::class is still used correctly in the body
        $this->assertStringContainsString("})->middleware(['auth', 'verified', SomeMiddleware::class])->name('dashboard');", $transformedContent);
        // Ensure original Route facade usage is gone from the method body
        $this->assertStringNotContainsString('Route::get', $transformedContent);
        // Ensure use statements are not duplicated in the method body
        $this->assertStringNotContainsString('        use App\Http\Middleware\SomeMiddleware;', $transformedContent);
    }

    public function testTransformControllerRoutes(): void
    {
        $originalContent = <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Admin\DashboardController; // Add another controller

Route::get('users', [UserController::class, 'index'])->name('users.index');
Route::post('users', [UserController::class, 'store'])->name('users.store');
Route::get('admin/dashboard', [DashboardController::class, 'show']);
PHP;

        $transformedContent = $this->transformer->transformRouteFile(
            $originalContent,
            'ApiRoute',
            'App\\Http\\Routes'
        );

        $this->assertNotNull($transformedContent);
        $this->assertStringContainsString('namespace App\\Http\\Routes;', $transformedContent);
        // Check original use statements are present
        $this->assertStringContainsString('use Illuminate\Support\Facades\Route;', $transformedContent);
        $this->assertStringContainsString('use App\Http\Controllers\UserController;', $transformedContent);
        $this->assertStringContainsString('use App\Http\Controllers\Admin\DashboardController;', $transformedContent);
        // Check standard use statements are present
        $this->assertStringContainsString('use Dentro\Yalr\BaseRoute;', $transformedContent);
        $this->assertStringContainsString('class ApiRoute extends BaseRoute', $transformedContent);
        // Check for replaced strings
        $this->assertStringContainsString("\$this->router->get('users', [UserController::class, 'index'])->name('users.index');", $transformedContent);
        $this->assertStringContainsString("\$this->router->post('users', [UserController::class, 'store'])->name('users.store');", $transformedContent);
        $this->assertStringContainsString("\$this->router->get('admin/dashboard', [DashboardController::class, 'show']);", $transformedContent);
        $this->assertStringNotContainsString('Route::get', $transformedContent);
        $this->assertStringNotContainsString('Route::post', $transformedContent);
        // Ensure use statements are not duplicated in the method body
        $this->assertStringNotContainsString('        use App\Http\Controllers\UserController;', $transformedContent);
    }

    public function testNoRoutesReturnsNull(): void
    {
        $originalContent = <<<'PHP'
<?php

// Just some code without routes
$variable = 'test';
echo $variable;
PHP;

        $transformedContent = $this->transformer->transformRouteFile(
            $originalContent,
            'EmptyRoute',
            'App\\Http\\Routes'
        );

        // The simplified transformer might still generate a class structure if <?php is present.
        // Adjusting the expectation based on the simplified logic.
        // If the goal is to return null ONLY if no Route:: exists, this test remains valid.
        $this->assertNull($transformedContent);
    }

    public function testTransformWithPrefix(): void
    {
        $originalContent = <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;

Route::get('users', function () {
    return response()->json(['message' => 'Users list']);
});
PHP;

        $transformedContent = $this->transformer->transformRouteFile(
            $originalContent,
            'ApiUsersRoute',
            'App\\Http\\Routes',
            '/api/v1' // Prefix is now only used for the class property
        );

        $this->assertNotNull($transformedContent);
        $this->assertStringContainsString('class ApiUsersRoute extends BaseRoute', $transformedContent);
        // Check prefix property is added
        $this->assertStringContainsString("protected string \$prefix = '/api/v1';", $transformedContent);
        // Check for replaced string, path remains unchanged by the transformer itself
        $this->assertStringContainsString("\$this->router->get('users', function () {", $transformedContent);
        $this->assertStringNotContainsString('Route::get', $transformedContent);
    }
}
