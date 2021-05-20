<center>YALR (Yet Another Laravel Router)</center>
---
Laravel classes custom route wrapper. Support PHP 8 Attributes and classes route. 
Previously known as [jalameta/router](https://github.com/jalameta/jps-router). Why would we publish it into new 
banner? well, we revamp a lot of things including abstraction that use strict type feature such as type definition and 
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
        - []

### Installation
Using composer :
> `composer require dentro/yalr`

### Requirements
 - PHP : "^8.0"
 - Laravel : "^8.0"

### Applying into your project
Run command in your project 
> `php artisan yalr:install {--remove}`

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
`php artisan make:route DefaultRoute`
After running command above, the route named `DefaultRoute` will appear in `app/Http/Routes/DefaultRoute.php`. After 
creating routes, it will not be loaded automatically, you must register the Route Class in the route configuration.

##### make:route options
1. Inject
Inject is useful options to auto adding the route class name within route configuration, so you don't need to add it 
   manually. E.g: 
`php artisan make:route DefaultRoute --inject web`
Command above will make the Default route within the web groups that defined in `config/routes.php`.

2. Controller
The controller option will generate the route and the controller used by the route. So you don't need to run 2 artisan 
   command to create a new controller and route.
`php artisan make:route DefaultRoute --controller HomeController`

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
TBD
