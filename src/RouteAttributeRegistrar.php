<?php

namespace Dentro\Yalr;

use Dentro\Yalr\Attributes\WithoutMiddleware;
use Illuminate\Routing\RouteRegistrar;
use Dentro\Yalr\Attributes\Domain;
use Dentro\Yalr\Attributes\Middleware;
use Dentro\Yalr\Attributes\Name;
use Dentro\Yalr\Attributes\Prefix;
use Dentro\Yalr\Attributes\Route;
use Dentro\Yalr\Attributes\RouteAttribute;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use function count;

class RouteAttributeRegistrar extends RouteRegistrar
{
    /**
     * Registering some class to router
     *
     * @param string $className
     */
    public function registerClass(string $className): void
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
                        break;
                    case $attribute instanceof WithoutMiddleware:
                        $carry['withoutMiddleware'] = $attribute->withoutMiddleware;
                        break;
                }

                return $carry;
            }, []);

        \count($options) > 0
            ? $this->router->group($options, fn() => $this->registerMethod($class))
            : $this->registerMethod($class);
    }

    /**
     * @param \ReflectionClass $class
     * @return void
     */
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
                        $withoutMiddleware = $attributeInstance->withoutMiddleware;

                        if (!empty($name)) {
                            $this->name($name);
                        }

                        if (!empty($middleware)) {
                            $this->middleware($middleware);
                        }

                        if (!empty($withoutMiddleware)) {
                            $this->withoutMiddleware($withoutMiddleware);
                        }

                        $this->router->addRoute($httpMethods, $uri, $this->compileAction($action));

                        // reset attributes after registered
                        $this->attributes = [];
                    });
            });
    }
}
