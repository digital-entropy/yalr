<h1 align="center">YALR (Yet Another Laravel Router)</h1>

![Test](https://github.com/digital-entropy/yalr/workflows/Test/badge.svg)
![Coding Standard](https://github.com/digital-entropy/yalr/workflows/Coding%20Standard/badge.svg)
[![codecov](https://codecov.io/gh/digital-entropy/yalr/branch/master/graph/badge.svg?token=NeWuwvwOAk)](https://codecov.io/gh/digital-entropy/yalr)
[![Total Downloads](https://poser.pugx.org/dentro/yalr/downloads)](//packagist.org/packages/dentro/yalr)
[![Laravel Octane Compatible](https://img.shields.io/badge/Laravel%20Octane-Compatible-success?style=flat&logo=laravel)](https://github.com/laravel/octane)

Laravel classes custom route wrapper. Support PHP 8 Attributes and classes route. 
Previously known as [jalameta/router](https://github.com/jalameta/jps-router). Then, why would we publish it into new 
banner ?, well, we revamp a lot of things including abstraction that use strict type feature such as type definition and 
return type, that's why we consider publishing this package under new banner.
<br><br>

### TABLE OF CONTENT
 - [Installation](#installation)
 - [Requirements](#requirements)
 - [Applying into your project](#applying-into-your-project)
 - [Usage](#usage)
    - [Class Wrapper Route](#class-wrapper-route)
        - [Creating New Route](#creating-new-route)
        - [Route Configuration](#routes-configuration)
        - [Class Structure](#class-structure)
        - [Using Controller](#using-controller)
        - [Route Prefix](#route-prefix)
        - [Route Name](#route-name)
    - [Route Attribute](#route-attribute)
        - [Available Class Target](#available-class-target)
        - [Available Method Target](#available-method-target)
        - [Added To Configuration Route](#added-to-configuration-route)

### Installation
Using composer :
```shell
composer require dentro/yalr
```

### Requirements
 - PHP : "^8.0"
 - Laravel : "^8.0"

### Applying into your project
Run command in your project 
```shell
php artisan yalr:install {--remove}
```

If you're deciding to remove the original laravel routes, you might add `--remove` option within the command. So the 
command will be 

> YALR will configure the routes for you, the Laravel default `routes` folder will be **deleted**. So backup your 
> defined routes first.

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
[Laravel Routing Docs](https://laravel.com/docs/6.x/routing).

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

### Route Attribute
PHP 8 comes up with a nice feature called `Attribute` see [this link](https://www.php.net/releases/8.0/en.php#attributes) for the detail. So we added those feature to this package for us to create something like this 
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
pretty cool right!. This feature is inspired by [spatie/laravel-route-attribute](https://github.com/spatie/laravel-route-attributes).

#### Available Class Target
```php
Dentro\Yalr\Attributes\Domain(string $domain);
Dentro\Yalr\Attributes\Prefix($prefix);
Dentro\Yalr\Attributes\Name(string $name, bool $dotPrefix = false, bool $dotSuffix = false);
Dentro\Yalr\Attributes\Middleware(string | array $middleware);
```

#### Available Method Target
```php
Dentro\Yalr\Attributes\Get(string $uri, ?string $name = null, array | string $middleware = []);
Dentro\Yalr\Attributes\Post(string $uri, ?string $name = null, array | string $middleware = []);
Dentro\Yalr\Attributes\Put(string $uri, ?string $name = null, array | string $middleware = []);
Dentro\Yalr\Attributes\Patch(string $uri, ?string $name = null, array | string $middleware = []);
Dentro\Yalr\Attributes\Delete(string $uri, ?string $name = null, array | string $middleware = []);
Dentro\Yalr\Attributes\Options(string $uri, ?string $name = null, array | string $middleware = []);
Dentro\Yalr\Attributes\Delete(string $uri, ?string $name = null, array | string $middleware = []);
```

#### Added To Configuration Route
just put class to your route configuration and yalr will figure it out what to do with your controller.

```php 
    'web' => [
        /** @inject web **/
	\App\Http\Routes\DefaultRoute::class,
        \App\Http\Controller\DashboardController::class,
    ],
```
