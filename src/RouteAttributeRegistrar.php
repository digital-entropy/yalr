<?php

namespace Jalameta\Router;

use Illuminate\Routing\Router;
use Jalameta\Router\Attributes\Domain;
use Jalameta\Router\Attributes\Middleware;
use Jalameta\Router\Attributes\Name;
use Jalameta\Router\Attributes\Prefix;
use Jalameta\Router\Attributes\Route;
use Jalameta\Router\Attributes\RouteAttribute;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;

class RouteAttributeRegistrar
{
    public function __construct(public Router $router)
    {
    }

    /**
     * Registering some class to router
     *
     * @param string $className
     * @throws \ReflectionException
     */
    public function registerClass(string $className)
    {
        if (!class_exists($className)) {
            return;
        }

        $class = new ReflectionClass($className);

        $attributes = $class->getAttributes(RouteAttribute::class, ReflectionAttribute::IS_INSTANCEOF);

        $options = collect($attributes)
            ->map(fn(ReflectionAttribute $attribute) => $attribute->newInstance())
            ->reduce(function ($carry, RouteAttribute $attribute) {
                switch (true) {
                    case $attribute instanceof Name:
                        $carry['as'] = $attribute->name;
                        break;
                    case $attribute instanceof Domain:
                        $carry['domain'] = $attribute->domain;
                        break;
                    case $attribute instanceof Prefix:
                        $carry['prefix'] = $attribute->prefix;
                        break;
                    case $attribute instanceof Middleware:
                        $carry['middleware'] = $attribute->middleware;
                }

                return $carry;
            }, []);

        count($options) > 0
            ? $this->router->group($options, fn() => $this->registerMethod($class))
            : $this->registerMethod($class);
    }

    protected function registerMethod(ReflectionClass $class): void
    {
        collect($class->getMethods())
            ->each(function (ReflectionMethod $reflectionMethod) use ($class) {
                collect($reflectionMethod->getAttributes(Route::class, ReflectionAttribute::IS_INSTANCEOF))
                    ->each(function (ReflectionAttribute $methodAttribute) use ($reflectionMethod, $class) {
                        /** @var Route $attributeInstance */
                        $attributeInstance = $methodAttribute->newInstance();

                        $httpMethods = $attributeInstance->method;
                        $action = $reflectionMethod->getName() === '__invoke'
                            ? $class->getName()
                            : [$class->getName(), $reflectionMethod->getName()];
                        $uri = $attributeInstance->uri;
                        $name = $attributeInstance->name;
                        $middleware = $attributeInstance->middleware;

                        $route = $this->router->addRoute($httpMethods, $uri, $action);

                        if (!empty($name)) {
                            $route->name($name);
                        }

                        if (!empty($middleware)) {
                            $route->middleware($middleware);
                        }
                    });
            });
    }

}
