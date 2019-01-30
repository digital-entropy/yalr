## JPS Router
-

Laravel Custom Route By Jalameta

If you want to add laravel auth, you should make auth first
``` php artisan make:auth ```

### Install JPS Route with Composer
```composer require jalameta/jps-router```

#### Initializing JPS Routes  
```php artisan jps:routes --install --remove ```

###### once installed it will removed all laravel default route.

#### Usage
List all registered JPS router class
``` php artisan jps:routes```

Generate Some Route 

``` php artisan make:route SomeNameRoute {--controller}```
````*{{ --contoller}} is optional````

Example syntax on File Routes
```php
class SomeClassRoute extends BaseRoute
{
    protected $name='SomeName'; //optional for uniformity for name of routes
    protected $prefix='SomePrefix';  //optional for uniformity url
     
    public function register()
    {
        $this->router->get($this->prefix('someURI'), [
            'as' => $this->name('specesificNameofRoute'),
            'uses' => $this->uses('specificNameofFunction') // it will use in your contoller
        ]);
    }
    
    public function controller()
    {
        return nameOfControllers::class; //if you not use {--controller}, you should add manualy
    }
}
``` 


To register your Custom Route, you should add in ``` config/routes.php```
```php
<?php
/**
 * Router configuration
 *
 * @author      veelasky <veelasky@gmail.com>
 */

return [
    'groups' => [
        'web' => [
            'middleware' => 'web',
            'prefix' => ''
        ],
        'api' => [
            'middleware' => 'api',
            'prefix' => 'api/v1'
        ]
    ],

    'web' => [
        your custom Route Class,
    ],
    'api' => [
        your custom Route Class,
    ]
];

```
