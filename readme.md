<h1 align="center">YALR (Yet Another Laravel Router)</h1>

![Test](https://github.com/digital-entropy/yalr/workflows/Test/badge.svg)
![Coding Standard](https://github.com/digital-entropy/yalr/workflows/Coding%20Standard/badge.svg)
[![codecov](https://codecov.io/gh/digital-entropy/yalr/branch/master/graph/badge.svg?token=NeWuwvwOAk)](https://codecov.io/gh/digital-entropy/yalr)
[![Total Downloads](https://poser.pugx.org/dentro/yalr/downloads)](https://packagist.org/packages/dentro/yalr)
[![Laravel Octane Compatible](https://img.shields.io/badge/Laravel%20Octane-Compatible-success?style=flat&logo=laravel)](https://github.com/laravel/octane)

Define Laravel routes in different ways using [Class Wrapper Route](#class-wrapper-route) or [Route Attribute](#route-attribute)

Previously known as [jalameta/router](https://github.com/jalameta/jps-router).

## Table of Contents

- [Installation](#installation)
- [Requirements](#requirements)
- [Getting Started](#getting-started)
- [Class Wrapper Route](#class-wrapper-route)
  - [Creating New Route](#creating-new-route)
  - [Routes Configuration](#routes-configuration)
  - [Class Structure](#class-structure)
  - [Using Controller](#using-controller)
  - [Route Prefix](#route-prefix)
  - [Route Name](#route-name)
- [Preloads](#preloads)
- [Route Attribute](#route-attribute)
  - [Available Class Target](#available-class-target)
  - [Available Method Target](#available-method-target)
  - [Detailed Attribute Examples](#detailed-attribute-examples)
- [Auto Controller Injection](#auto-controller-injection)
- [Available Commands](#available-commands)
  - [yalr:install](#yalrinstall)
  - [yalr:display](#yalrdisplay)
  - [yalr:generate](#yalrgenerate)
  - [make:route](#makeroute)

## Installation

Using Composer:

```shell
composer require dentro/yalr
```

## Requirements

| Laravel | Yalr | PHP  |
|---------|------|------|
| 8.x     | ^1.0 | ^8.0 |
| 9.x     | ^1.1 | ^8.0 |
| 10.x    | ^1.2 | ^8.0 |
| 11.x    | ^1.3 | ^8.1 |
| 12.x    | ^1.4 | ^8.2 |
| 12.x    | ^1.5 | ^8.3 |

## Getting Started

After installation, run the following command in your project:

```shell
php artisan yalr:install
```

## Class Wrapper Route

Class wrapper route is our effort to make routing in Laravel more expressive and organized. Routes are represented by their namespace for easier understanding. For example, class `App\Admin\TransactionRoute` will correspond to the route `/app/admin/transaction`.

### Creating New Route

To create a new route, run:

```shell
php artisan make:route DefaultRoute
```

This command will create a route named `DefaultRoute` in `app/Http/Routes/DefaultRoute.php`. Note that after creation, you must register the Route class in your route configuration for it to be loaded.

#### make:route Options

##### 1. Inject

The `--inject` option automatically adds the route class name to your route configuration:

```shell
php artisan make:route DefaultRoute --inject web
```

This command creates the Default route and adds it to the web group defined in `config/routes.php`.

##### 2. Controller

The `--controller` option generates both the route and its associated controller:

```shell
php artisan make:route DefaultRoute --controller HomeController
```

This eliminates the need to run two separate commands to create a controller and route.

##### 3. Help

Shows the YALR command help information.

### Routes Configuration

Here's an example of a YALR configuration file:

```php
return [
    'groups' => [
        'web' => [
            'middleware' => 'web',
            'prefix' => '',
        ],
        'api' => [
            'middleware' => 'api',
            'prefix' => 'api',
        ],
    ],

    'web' => [
        /** @inject web **/
        \App\Http\Routes\DefaultRoute::class,
    ],
    'api' => [
        /** @inject api **/
    ],

    // Auto-injection configuration for yalr:generate command
    'injects' => [
        'web' => ['app/Controllers/Web/'],
        'api' => ['app/Controllers/Api/']
    ],
];
```

The `groups` section defines group configurations where you can specify Laravel options such as `as`, `domain`, `middleware`, `prefix`, etc. The `web` and `api` sections contain arrays of route class names that belong to these groups.

### Class Structure

Here's an example of a generated route file:

```php
<?php

namespace App\Http\Routes;

use Dentro\Yalr\BaseRoute;

class DefaultRoute extends BaseRoute
{
    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void
    {
        // Make an awesome route
    }
}
```

Define routes within the `register` method by calling `$this->router`, which is a router instance. You can invoke Laravel routing methods such as `get`, `post`, `put`, etc. See [Laravel Routing Documentation](https://laravel.com/docs/9.x/routing) for more details.

> **Note:** Avoid using closure actions, as your application will encounter errors when routes are cached.

Example with prefix and name properties:

```php
<?php

namespace App\Http\Routes;

use Dentro\Yalr\BaseRoute;

class DefaultRoute extends BaseRoute
{
    protected string $prefix = 'wonderful';
    protected string $name = 'wonderful';

    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void
    {
        $this->router->get('/', function () {
            return view('welcome');
        });
    }
}
```

### Using Controller

When creating a route with the controller option, the controller class will be referenced in a controller method:

```php
<?php

namespace App\Http\Routes;

use Dentro\Yalr\BaseRoute;
use App\Http\Controllers\HomeController;

class DefaultRoute extends BaseRoute
{
    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void
    {
        $this->router->get('/', [
            'uses' => $this->uses('index')
        ]);
    }

     /**
     * Controller used by this route.
     *
     * @return string
     */
    public function controller(): string
    {
        return HomeController::class;
    }
}
```

This route is equivalent to:

```php
Route::get('/', [
    'uses' => "App\Http\Controllers\HomeController@index"
]);
```

This package helps eliminate duplicate namespace and class name references in your route definitions. If you want to use a different controller than the one specified in the `controller()` method, you can pass the controller class as the second parameter of `$this->uses()`:

```php
$this->uses('login', LoginController::class)
```

### Route Prefix

You can override the default prefix (which is '/') by defining it in your class property:

```php
protected string $prefix = '/home';
```

Then use it in your routes:

```php
$this->router->get($this->prefix(), [
    'uses' => $this->uses('index')
]);
```

This is equivalent to:

```php
Route::get('/home', [
    'uses' => "App\Http\Controllers\HomeController@index"
]);
```

### Route Name

Define a route name property in your route class:

```php
protected string $name = 'home';
```

Then use the `$this->name()` method, which adds a dot (.) between the route group name and the individual route name:

```php
$this->router->get('/', [
    'as' => $this->name('landing'),
    'uses' => $this->uses('index')
]);
```

This is equivalent to:

```php
Route::get('/', [
    'as' => 'home.landing',
    'uses' => "App\Http\Controllers\HomeController@index",
]);
```

## Preloads

Preloads always run even when routes are cached. They're an ideal place for route model binding and rate limiters:

```php
// config/routes.php

'preloads' => [
    App\Http\RouteModelBinding::class,
    App\Http\RouteRateLimiter::class,
],
```

```php
namespace App\Http;

use Dentro\Yalr\Contracts\Bindable;

class RouteModelBinding implements Bindable
{
    public function __construct(protected Router $router)
    {
    }

    public function bind(): void
    {
        $this->router->bind('fleet_hash', fn ($value) => Fleet::byHashOrFail($value));
        $this->router->bind('office_slug', fn ($value) => Office::query()->where('slug', $value)->firstOrFail());
    }
}
```

```php
namespace App\Http;

use Dentro\Yalr\Contracts\Bindable;

class RouteRateLimiter implements Bindable
{
    public function __construct(protected Router $router)
    {
    }

    public function bind(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(360)->by($request->user()?->email.$request->ip());
        });
    }
}
```

## Route Attribute

PHP 8 introduced a feature called Attributes (see [PHP 8 Attributes](https://www.php.net/releases/8.0/en.php#attributes)). YALR leverages this feature to enable more elegant route definitions:

```php
#[Middleware(['auth:sanctum', 'verified'])]
class DashboardController extends Controller
{
    #[Get('dashboard', name: 'dashboard')]
    public function index(): Response
    {
        return Inertia::render('Dashboard');
    }
}
```

### Available Class Target

```php
Dentro\Yalr\Attributes\Domain(string $domain);
Dentro\Yalr\Attributes\Prefix($prefix);
Dentro\Yalr\Attributes\Name(string $name, bool $dotPrefix = false, bool $dotSuffix = false);
Dentro\Yalr\Attributes\Middleware(string | array $middleware);
```

### Available Method Target

```php
Dentro\Yalr\Attributes\Get(string $uri, ?string $name = null, array | string $middleware = [], array | string $withoutMiddleware = []);
Dentro\Yalr\Attributes\Post(string $uri, ?string $name = null, array | string $middleware = [], array | string $withoutMiddleware = []);
Dentro\Yalr\Attributes\Put(string $uri, ?string $name = null, array | string $middleware = [], array | string $withoutMiddleware = []);
Dentro\Yalr\Attributes\Patch(string $uri, ?string $name = null, array | string $middleware = [], array | string $withoutMiddleware = []);
Dentro\Yalr\Attributes\Delete(string $uri, ?string $name = null, array | string $middleware = [], array | string $withoutMiddleware = []);
Dentro\Yalr\Attributes\Options(string $uri, ?string $name = null, array | string $middleware = [], array | string $withoutMiddleware = []);
```

### Detailed Attribute Examples

#### Basic Controller with Multiple Routes

```php
<?php

namespace App\Http\Controllers;

use Dentro\Yalr\Attributes\Get;
use Dentro\Yalr\Attributes\Post;
use Dentro\Yalr\Attributes\Put;
use Dentro\Yalr\Attributes\Delete;
use Dentro\Yalr\Attributes\Prefix;
use Dentro\Yalr\Attributes\Name;

#[Prefix('users')]
#[Name('users', dotSuffix: true)]
class UserController extends Controller
{
    #[Get('/', name: 'index')]
    public function index()
    {
        // GET /users
        // Route name: users.index
        return view('users.index');
    }

    #[Get('/create', name: 'create')]
    public function create()
    {
        // GET /users/create
        // Route name: users.create
        return view('users.create');
    }

    #[Post('/', name: 'store')]
    public function store()
    {
        // POST /users
        // Route name: users.store
        // ... store logic
        return redirect()->route('users.index');
    }

    #[Get('/{id}', name: 'show')]
    public function show($id)
    {
        // GET /users/{id}
        // Route name: users.show
        return view('users.show', ['user' => User::findOrFail($id)]);
    }

    #[Get('/{id}/edit', name: 'edit')]
    public function edit($id)
    {
        // GET /users/{id}/edit
        // Route name: users.edit
        return view('users.edit', ['user' => User::findOrFail($id)]);
    }

    #[Put('/{id}', name: 'update')]
    public function update($id)
    {
        // PUT /users/{id}
        // Route name: users.update
        // ... update logic
        return redirect()->route('users.show', $id);
    }

    #[Delete('/{id}', name: 'destroy')]
    public function destroy($id)
    {
        // DELETE /users/{id}
        // Route name: users.destroy
        // ... delete logic
        return redirect()->route('users.index');
    }
}
```

#### Controller with Middleware

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Dentro\Yalr\Attributes\Get;
use Dentro\Yalr\Attributes\Middleware;
use Dentro\Yalr\Attributes\Prefix;
use Dentro\Yalr\Attributes\Name;
use Dentro\Yalr\Attributes\Domain;

#[Prefix('admin/dashboard')]
#[Name('admin.dashboard')]
#[Middleware(['auth', 'admin'])]
// You can also set a specific domain for these routes
#[Domain('admin.example.com')]
class DashboardController extends Controller
{
    #[Get('/', name: 'index')]
    public function index()
    {
        // GET /admin/dashboard
        // Route name: admin.dashboard.index
        // Applied middleware: auth, admin
        return view('admin.dashboard.index');
    }

    // You can override or add route-specific middleware
    #[Get('/stats', name: 'stats')]
    #[Middleware(['cache:60'])]
    public function stats()
    {
        // GET /admin/dashboard/stats
        // Route name: admin.dashboard.stats
        // Applied middleware: auth, admin, cache:60
        return view('admin.dashboard.stats');
    }

    #[Get('/settings', name: 'settings')]
    // You can provide a specific route middleware for this method only
    #[Middleware(['can:edit-settings'])]
    public function settings()
    {
        // GET /admin/dashboard/settings
        // Route name: admin.dashboard.settings
        // Applied middleware: auth, admin, can:edit-settings
        return view('admin.dashboard.settings');
    }
}
```

#### API Controller Example

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Dentro\Yalr\Attributes\Get;
use Dentro\Yalr\Attributes\Post;
use Dentro\Yalr\Attributes\Put;
use Dentro\Yalr\Attributes\Delete;
use Dentro\Yalr\Attributes\Prefix;
use Dentro\Yalr\Attributes\Middleware;
use Dentro\Yalr\Attributes\Name;

#[Prefix('api/v1/posts')]
#[Name('api.posts')]
#[Middleware(['auth:sanctum'])]
class PostController extends Controller
{
    #[Get('/', name: 'index')]
    public function index()
    {
        // GET /api/v1/posts
        // Route name: api.posts.index
        return Post::all();
    }

    #[Post('/', name: 'store')]
    public function store(Request $request)
    {
        // POST /api/v1/posts
        // Route name: api.posts.store
        $post = Post::create($request->validated());
        return response()->json($post, 201);
    }

    #[Get('/{id}', name: 'show')]
    public function show($id)
    {
        // GET /api/v1/posts/{id}
        // Route name: api.posts.show
        return Post::findOrFail($id);
    }

    #[Put('/{id}', name: 'update')]
    public function update(Request $request, $id)
    {
        // PUT /api/v1/posts/{id}
        // Route name: api.posts.update
        $post = Post::findOrFail($id);
        $post->update($request->validated());
        return $post;
    }

    #[Delete('/{id}', name: 'destroy')]
    public function destroy($id)
    {
        // DELETE /api/v1/posts/{id}
        // Route name: api.posts.destroy
        Post::findOrFail($id)->delete();
        return response()->noContent();
    }
}
```

### Adding Controllers to Route Configuration

Simply add controller classes to your route configuration, and YALR will determine how to handle them:

```php
'web' => [
    /** @inject web **/
    \App\Http\Routes\DefaultRoute::class,
    \App\Http\Controllers\UserController::class,
],
'api' => [
    /** @inject api **/
    \App\Http\Controllers\Api\PostController::class,
],
```

## Auto Injection

YALR provides automatic class injection through the configuration file. You can specify directories containing either your controllers with PHP 8 attributes or your route wrapper classes, and YALR will scan and inject them into your routes configuration.

Configure the `injects` section in `config/routes.php`:

```php
'injects' => [
    'web' => ['app/Http/Controllers/Web/'],
    'api' => ['app/Http/Controllers/Api/']
],
```

You can specify multiple directories for each group:

```php
'injects' => [
    'web' => [
        'app/Http/Controllers/Web/',      // For controllers with attributes
        'app/Http/Routes/Web/',           // For route wrapper classes
        'app/Http/Controllers/Admin/'
    ],
    'api' => 'app/Http/Controllers/Api/' // Single directory can be a string
],
```

> **Note:** The directory scan is not recursive. YALR will only read files in the specified directories and won't search within their subdirectories.

To generate routes based on your class structure, run:

```shell
php artisan yalr:generate
```

## Available Commands

### yalr:install

Installs YALR into your Laravel project by publishing the necessary configuration files:

```shell
php artisan yalr:install
```

#### Options:

- `--transform`: Transform existing Laravel route files to YALR format
- `--backup`: Create backups of original route files when transforming

When using `--transform`, YALR will:

1. Scan your `routes` directory for traditional Laravel route files
2. Convert Route facade calls to YALR class-based format
3. Store the new route classes in `app/Http/Routes` directory
4. Preserve middleware, prefixes, and other route configurations
5. Create backups of original files with `.bak` extension if `--backup` is specified

### yalr:display

Displays all registered routes grouped by their configuration sections:

```shell
php artisan yalr:display
```

### yalr:generate

Scans controller directories specified in the `injects` configuration and automatically adds them to the appropriate route groups:

```shell
php artisan yalr:generate
```

### make:route

Creates a new route class with optional controller generation and route injection:

```shell
# Basic usage
php artisan make:route UserRoute
```

#### Options:

- `--controller` or `-c`: Generate a controller to accompany the route class
- `--inject` or `-j`: Automatically inject the route class into the specified route group
`
`
