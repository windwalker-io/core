<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Router;

use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\NullPackage;

/**
 * The PackageRouter class, it is a decoration pattern to wrap package and router object.
 *
 * @since  2.0
 */
class PackageRouter implements RouteBuilderInterface
{
    use RouteBuilderTrait;

    /**
     * Property package.
     *
     * @var  AbstractPackage
     */
    protected $package;

    /**
     * Property router.
     *
     * @var MainRouter
     */
    protected $router;

    /**
     * Class init.
     *
     * @param MainRouter      $router
     * @param AbstractPackage $package
     */
    public function __construct(MainRouter $router, AbstractPackage $package = null)
    {
        $this->router = $router;

        $this->setPackage($package);
    }

    /**
     * build
     *
     * @param string $route
     * @param array  $queries
     * @param array  $config
     *
     * @return string
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function route($route, $queries = [], $config = [])
    {
        try {
            if ($this->router->hasRoute($this->package->getName() . '@' . $route)) {
                return $this->router->build($this->package->getName() . '@' . $route, $queries, $config);
            }

            return $this->router->build($route, $queries, $config);
        } catch (\OutOfRangeException $e) {
            if ($this->package->app->get('routing.debug', true)) {
                throw new \OutOfRangeException($e->getMessage(), $e->getCode(), $e);
            }

            return '#';
        }
    }

    /**
     * __call
     *
     * @param string $name
     * @param array  $args
     *
     * @return  mixed
     */
    public function __call($name, $args)
    {
        return $this->getRouter()->$name(...$args);
    }

    /**
     * Method to get property Router
     *
     * @return  MainRouter
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * Method to set property router
     *
     * @param   MainRouter $router
     *
     * @return  static  Return self to support chaining.
     */
    public function setRouter(MainRouter $router)
    {
        $this->router = $router;

        return $this;
    }

    /**
     * getMatched
     *
     * @return  \Windwalker\Router\Route
     */
    public function getMatched()
    {
        return $this->router->getMatched();
    }

    /**
     * Method to get property Package
     *
     * @return  AbstractPackage
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * Method to set property package
     *
     * @param   AbstractPackage $package
     *
     * @return  static  Return self to support chaining.
     */
    public function setPackage(AbstractPackage $package = null)
    {
        if ($package === null) {
            $package = new NullPackage();
        }

        $this->package = $package;

        return $this;
    }
}
