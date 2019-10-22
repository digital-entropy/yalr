<?php


namespace Jalameta\Router;


use Jalameta\Router\Concerns\RouteController;
use Jalameta\Router\Contracts\Bindable;

/**
 * Base Group Router
 *
 * @author      rendyananta <rendyananta66@gmail.com>
 */
abstract class GroupRoute implements Bindable
{
    use RouteController;

    /**
     * Route path prefix.
     *
     * @var string
     */
    public $prefix = '/';

    /**
     * Registered route name.
     *
     * @var string
     */
    public $name;

    /**
     * Middleware used in route
     *
     * @var array|string
     */
    public $middleware;

    /**
     * Route for specific domain
     *
     * @var string
     */
    public $domain;

    /**
     * Route for specific regular expression
     *
     * @var array|string
     */
    public $regex;

    /**
     * Router Registrar.
     *
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    /**
     * SelfBindingRoute constructor.
     */
    public function __construct()
    {
        $this->router = app('router');
    }

    /**
     * Bind and register the current route.
     *
     * @return void
     */
    public function bind()
    {
        $this->register();
        $this->afterRegister();
    }

    /**
     * Performs callback after register route
     *
     * @return mixed|void
     */
    public function afterRegister()
    {
        //
    }

    /**
     * Add dot before route name
     *
     * @param null $suffix
     * @return string
     */
    public function name($suffix = null)
    {
        if (empty($suffix)) {
            return $this->name;
        }

        return $this->name.'.'.$suffix;
    }

}
