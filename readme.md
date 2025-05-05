<h1 align="center">YALR (Yet Another Laravel Router)</h1>

![Test](https://github.com/digital-entropy/yalr/workflows/Test/badge.svg)
![Coding Standard](https://github.com/digital-entropy/yalr/workflows/Coding%20Standard/badge.svg)
[![codecov](https://codecov.io/gh/digital-entropy/yalr/branch/master/graph/badge.svg?token=NeWuwvwOAk)](https://codecov.io/gh/digital-entropy/yalr)
[![Total Downloads](https://poser.pugx.org/dentro/yalr/downloads)](https://packagist.org/packages/dentro/yalr)
[![Laravel Octane Compatible](https://img.shields.io/badge/Laravel%20Octane-Compatible-success?style=flat&logo=laravel)](https://github.com/laravel/octane)

Define Laravel routes in different ways using [Class Wrapper Route](#class-wrapper-route) or [Route Attribute](#route-attribute)

Previously known as [jalameta/router](https://github.com/jalameta/jps-router).<br><br>

### TABLE OF CONTENT

-   [Installation](#installation)
-   [Requirements](#requirements)
-   [Applying into your project](#applying-into-your-project)
-   [Usage](#usage)
    -   [Class Wrapper Route](#class-wrapper-route)
        -   [Creating New Route](#creating-new-route)
        -   [Route Configuration](#routes-configuration)
        -   [Class Structure](#class-structure)
        -   [Using Controller](#using-controller)
        -   [Route Prefix](#route-prefix)
        -   [Route Name](#route-name)
    -   [Preloads](#preloads)
    -   [Route Attribute](#route-attribute)
        -   [Available Class Target](#available-class-target)
        -   [Available Method Target](#available-method-target)
        -   [Detailed Attribute Examples](#detailed-attribute-examples)
    -   [Auto Controller Injection](#auto-controller-injection)
-   [Available Commands](#available-commands)
    -   [yalr:install](#yalrinstall)
    -   [yalr:display](#yalrdisplay)
    -   [yalr:generate](#yalrgenerate)
    -   [make:route](#makeroute)

### Installation

Using composer :

```shell
composer require dentro/yalr
```

### Requirements

| Laravel | Yalr |
| ------- | ---- |
| 8.x     | ^1.0 |
| 9.x     | ^1.1 |
| 10.x    | ^1.2 |
| 11.x    | ^1.3 |
| 12.x    | ^1.4 |

### Applying into your project

Run command in your project

```shell
php artisan yalr:install
```

### Usage

### Class Wrapper Route

Class wrapper route is our effort to make routing in laravel more expressive and separated. We usually make route that
representative with namespace for easy to understand. For example, class `App\Admin\TransactionRoute` will represent
route `/app/admin/transaction`.

#### Creating new route

To make a new route just run:

```shell
php artisan make:route DefaultRoute
```

After running command above, the route named `DefaultRoute` will appear in `app/Http/Routes/DefaultRoute.php`. After
creating routes, it will not be loaded automatically, you must register the Route Class in the route configuration.

##### make:route options

1. Inject
   Inject is useful options to auto adding the route class name within route configuration, so you don't need to add it
   manually. E.g:

```shell
php artisan make:route DefaultRoute --inject web
```

Command above will make the Default route within the web groups that defined in `config/routes.php`.

2. Controller
   The controller option will generate the route and the controller used by the route. So you don't need to run 2 artisan
   command to create a new controller and route.

```shell
php artisan make:route DefaultRoute --controller HomeController
```

3. Help
   Shows YALR command helps

#### Routes Configuration

Below is an example of YALR configurations.

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

As you can see, `groups` index is group configuration, you can pass any laravel options there such as `as`, `domain`,
`middleware`, `prefix`, etc. Afterward, the `web` and `api` are group index defined before in the `groups` index. It is an array of route class names.

#### Class Structure

After creating a route with the command, we will see the example of the generated file.

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

After creating a route with the command, we will see the example of the generated file. We can define routes within the
register method. All you need is to call $this->router as a router instance. Then, we can invoke the laravel routing method such as post, put, etc. See
[Laravel Routing Docs](https://laravel.com/docs/9.x/routing).

> Avoid using closure action, otherwise your application will encounter error when routes were cached.

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

##### Using Controller

From create route command, we know we can pass the controller namespace. The created controller will show up in the
route class as a controller method.

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

The route above is equal with

```php
Route::get('/', [
    'uses' => "App\Http\Controllers\HomeController@index"
]);
```

This package want to solve those duplicated namespace and class name several times as we define the routes.
Or if you don't want to use the controller in the route class, you can pass the second parameter of `$this->uses()`
method with the controller class name to be used, E.g : `$this->uses('login', LoginController::class)`.

##### Route Prefix

Override the route prefix defined in the class property. Default prefix is '/';

> `protected string $prefix = '/home';`

```php
$this->router->get($this->prefix(), [
    'uses' => $this->uses('index')
]);
```

The route above is equal with

```php
Route::get('/home', [
    'uses' => "App\Http\Controllers\HomeController@index"
]);
```

##### Route Name

You need to define the route name property within the route class

> `protected string $name = 'home';`

Later we can use `$this->name()` method for adding separation with dot (.) between the route group name, and the single
route name

```php
$this->router->get('/', [
    'as' => $this->name('landing'),
    'uses' => $this->uses('index')
]);
```

It equal with

```php
Route::get('/', [
    'as' => 'home.landing',
    'uses' => "App\Http\Controllers\HomeController@index",
]);
```

### Preloads

Preloads always run even though routes been cached. It might be the good place to put route model binding and rate limiter there.<br> Example :

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

### Route Attribute

PHP 8 comes up with a nice feature called `Attribute` see [this link](https://www.php.net/releases/8.0/en.php#attributes) for the detail. So we added those feature to this package for us to create something like the example below.

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

#### Available Class Target

```php
Dentro\Yalr\Attributes\Domain(string $domain);
Dentro\Yalr\Attributes\Prefix($prefix);
Dentro\Yalr\Attributes\Name(string $name, bool $dotPrefix = false, bool $dotSuffix = false);
Dentro\Yalr\Attributes\Middleware(string | array $middleware);
```

#### Available Method Target

```php
Dentro\Yalr\Attributes\Get(string $uri, ?string $name = null, array | string $middleware = [], array | string $withoutMiddleware = []);
Dentro\Yalr\Attributes\Post(string $uri, ?string $name = null, array | string $middleware = [], array | string $withoutMiddleware = []);
Dentro\Yalr\Attributes\Put(string $uri, ?string $name = null, array | string $middleware = [], array | string $withoutMiddleware = []);
Dentro\Yalr\Attributes\Patch(string $uri, ?string $name = null, array | string $middleware = [], array | string $withoutMiddleware = []);
Dentro\Yalr\Attributes\Delete(string $uri, ?string $name = null, array | string $middleware = [], array | string $withoutMiddleware = []);
Dentro\Yalr\Attributes\Options(string $uri, ?string $name = null, array | string $middleware = [], array | string $withoutMiddleware = []);
```

#### Detailed Attribute Examples

##### Basic Controller with Multiple Routes

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

##### Controller with Middleware

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

##### API Controller Example

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

#### Added To Configuration Route

just put class to your route configuration and yalr will figure it out what to do with your controller.

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

### Auto Controller Injection

YALR provides automatic controller injection functionality through the configuration file. This feature allows you to specify directories where your controllers are located, and YALR will automatically scan and inject them into your routes.

To use this feature, configure the `injects` section in your `config/routes.php`:

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
        'app/Http/Controllers/Web/',
        'app/Http/Controllers/Admin/'
    ],
    'api' => 'app/Http/Controllers/Api/' // Single directory can be a string
],
```

To generate routes based on your controller structure, run:

```shell
php artisan yalr:generate
```

This will scan the directories specified in the `injects` configuration and add the discovered controllers to their respective route groups.

## Available Commands

YALR provides several helpful commands to simplify working with class-based routes and controller attribute routing.

### yalr:install

Installs YALR into your Laravel project by publishing the necessary configuration files.

```shell
php artisan yalr:install
```

#### Options:

-   `--transform`: Transform existing Laravel route files to YALR format

    ```shell
    php artisan yalr:install --transform
    ```

-   `--backup`: Create backup of original route files when transforming
    ```shell
    php artisan yalr:install --transform --backup
    ```

When using the `--transform` option, YALR will:

1. Scan your `routes` directory for traditional Laravel route files
2. Convert Route facade calls to YALR class-based format
3. Store the new route classes in `app/Http/Routes` directory
4. Preserve middleware, prefixes and other route configurations
5. Create backups of original files with `.bak` extension if `--backup` is specified

### yalr:display

Displays all registered routes grouped by their configuration sections to help you visualize your route organization.

```shell
php artisan yalr:display
```

Example output:

```
+----------------------------------+------+
| Route Class                      | Group |
+----------------------------------+------+
| \App\Http\Routes\WebRoutes       | web  |
| \App\Http\Routes\AuthRoutes      | web  |
| \App\Http\Controllers\UserController | web  |
| \App\Http\Routes\ApiRoutes       | api  |
| \App\Http\Controllers\Api\PostController | api  |
+----------------------------------+------+
```

This is particularly useful when debugging route registrations or checking which controllers are being registered as routes.

### yalr:generate

Scans controller directories specified in the `injects` configuration and automatically adds them to the appropriate route groups. This is useful for automatic controller discovery and registration.

```shell
php artisan yalr:generate
```

This command:

1. Reads the `injects` configuration from your routes config file
2. Scans each specified directory for controller classes
3. Adds discovered controller classes to their respective route groups
4. Preserves existing routes and only adds new controllers

Example workflow:

1. Set up directories to scan in your `config/routes.php`:
    ```php
    'injects' => [
        'web' => ['app/Http/Controllers/Web/', 'app/Http/Controllers/Admin/'],
        'api' => 'app/Http/Controllers/Api/'
    ]
    ```
2. Create controllers with route attributes in those directories
3. Run `php artisan yalr:generate` to automatically register them

The command outputs detailed information about the scanning process:

```
Scanning directory for 'web' group: app/Http/Controllers/Web/
Found 3 controller class(es) in app/Http/Controllers/Web/
Added \App\Http\Controllers\Web\UserController::class to 'web' group
Added \App\Http\Controllers\Web\ProductController::class to 'web' group
Added \App\Http\Controllers\Web\OrderController::class to 'web' group
Scanning directory for 'api' group: app/Http/Controllers/Api/
Found 2 controller class(es) in app/Http/Controllers/Api/
Added \App\Http\Controllers\Api\UserController::class to 'api' group
Added \App\Http\Controllers\Api\ProductController::class to 'api' group
Successfully injected 5 controller(s) into route groups.
```

### make:route

Creates a new route class with optional controller generation and route injection.

```shell
# Basic usage
php artisan make:route UserRoute
```

#### Options:

-   `--controller` or `-c`: Generate a controller to accompany the route class

    ```shell
    php artisan make:route UserRoute --controller
    ```

    This will create both `app/Http/Routes/UserRoute.php` and `app/Http/Controllers/UserController.php`

-   `--inject` or `-j`: Automatically inject the route class into the specified route group
    ```shell
    php artisan make:route UserRoute --inject web
    ```
    This adds `\App\Http\Routes\UserRoute::class` to the `web` group in your routes config file

You can combine both options:

```shell
php artisan make:route AdminDashboardRoute --controller --inject admin
```

The generated route will be placed in `app/Http/Routes` by default and follows this structure:

```php
<?php

namespace App\Http\Routes;

use Dentro\Yalr\BaseRoute;

class UserRoute extends BaseRoute
{
    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void
    {
        // Define your routes here
        $this->router->get('users', function () {
            // Route implementation
        });
    }
}
```

When the `--controller` option is used, the route class will include controller integration:

```php
<?php

namespace App\Http\Routes;

use Dentro\Yalr\BaseRoute;
use App\Http\Controllers\UserController;

class UserRoute extends BaseRoute
{
    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void
    {
        $this->router->get('users', [
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
        return UserController::class;
    }
}
```
