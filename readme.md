##JPS Router

###Introduction
JPS Router is Laravel Custom Route By Jalameta

## Installation
### Using Composer
```composer require jalameta/jps-router```
#### I don't have composer
you can download it [here](https://getcomposer.org/download/).

### Installation on your project
**Warning** if you want to generate laravel auth you should do before this command below

```php artisan jps:routes --install --remove ```

_note: {{ --remove }} it will removed all laravel default route._
 
   

#####Display help information about jps router
``php artisan jps:routes --help`` 

## Usage
####Generate Some Route

``php artisan make:route {{ route name }}``

####Generate Some Route & Controller

``php artisan make:route {{ route name }} {{ --controller }}``

####Generate Some Route & Auto registered on routes.php
``php artisan make:route {{ route name }} {{ --inject (web/api) }}``

#####Display help information about make route
``php artisan make:route --help``

####List all registered JPS router class
``` php artisan jps:routes```
