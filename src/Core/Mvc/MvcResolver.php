<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Mvc;

use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Utilities\Queue\Priority;
use Windwalker\Utilities\Queue\PriorityQueue;

/**
 * The MvcResolver class. This is a composite class to wrap Controller, Model, View resolvers.
 *
 * @since  3.0
 */
class MvcResolver
{
    /**
     * Property controllerResolver.
     *
     * @var  ControllerResolver
     */
    protected $controllerResolver;

    /**
     * Property RepositoryResolver.
     *
     * @var  RepositoryResolver
     */
    protected $repositoryResolver;

    /**
     * Property viewResolver.
     *
     * @var  ViewResolver
     */
    protected $viewResolver;

    /**
     * create
     *
     * @param AbstractPackage $package
     *
     * @return  static
     */
    public static function create(AbstractPackage $package)
    {
        return new static(
            new ControllerResolver($package, $package->getContainer()),
            new RepositoryResolver($package, $package->getContainer()),
            new ViewResolver($package, $package->getContainer())
        );
    }

    /**
     * MvcResolver constructor.
     *
     * @param ControllerResolver $controllerResolver
     * @param RepositoryResolver $repositoryResolver
     * @param ViewResolver       $viewResolver
     */
    public function __construct(
        ControllerResolver $controllerResolver,
        RepositoryResolver $repositoryResolver,
        ViewResolver $viewResolver
    ) {
        $this->controllerResolver = $controllerResolver;
        $this->repositoryResolver = $repositoryResolver;
        $this->viewResolver       = $viewResolver;
    }

    /**
     * Resolve class path.
     *
     * @param   string|AbstractPackage $package
     * @param   string                 $name
     *
     * @return  string|false
     */
    public function resolveRepository($package, $name)
    {
        return $this->repositoryResolver->resolve($name);
    }

    /**
     * resolveModel
     *
     * @param   string|AbstractPackage $package
     * @param   string                 $name
     *
     * @return  false|string
     *
     * @since  __DEPLOY_VERSION__
     *
     * @deprecated  Use resolveRepository() instead.
     */
    public function resolveModel($package, $name)
    {
        return $this->resolveRepository($package, $name);
    }

    /**
     * Resolve class path.
     *
     * @param   string|AbstractPackage $package
     * @param   string                 $name
     *
     * @return  string|false
     */
    public function resolveView($package, $name)
    {
        return $this->viewResolver->resolve($name);
    }

    /**
     * Resolve class path.
     *
     * @param   string|AbstractPackage $package
     * @param   string                 $name
     *
     * @return  string|false
     */
    public function resolveController($package, $name)
    {
        return $this->controllerResolver->resolve($name);
    }

    /**
     * addNamespace
     *
     * @param string $namespace
     * @param int    $priority
     *
     * @return  static
     */
    public function addNamespace($namespace, $priority = PriorityQueue::NORMAL)
    {
        $this->controllerResolver->addNamespace($namespace . '\Controller', $priority);
        $this->repositoryResolver->addNamespace($namespace . '\Model', $priority);
        $this->viewResolver->addNamespace($namespace . '\View', $priority);

        return $this;
    }

    /**
     * reset
     *
     * @return  static
     */
    public function reset()
    {
        $this->controllerResolver->reset();
        $this->repositoryResolver->reset();
        $this->viewResolver->reset();

        return $this;
    }

    /**
     * Method to get property ControllerResolver
     *
     * @return  ControllerResolver
     */
    public function getControllerResolver()
    {
        return $this->controllerResolver;
    }

    /**
     * Method to set property controllerResolver
     *
     * @param   ControllerResolver $controllerResolver
     *
     * @return  static  Return self to support chaining.
     */
    public function setControllerResolver($controllerResolver)
    {
        $this->controllerResolver = $controllerResolver;

        return $this;
    }

    /**
     * Method to get property ModelResolver
     *
     * @return  RepositoryResolver
     */
    public function getRepositoryResolver()
    {
        return $this->repositoryResolver;
    }

    /**
     * Method to set property modelResolver
     *
     * @param   RepositoryResolver $repositoryResolver
     *
     * @return  static  Return self to support chaining.
     */
    public function setRepositoryResolver($repositoryResolver)
    {
        $this->repositoryResolver = $repositoryResolver;

        return $this;
    }

    /**
     * Method to get property ModelResolver
     *
     * @return  RepositoryResolver
     *
     * @deprecated Use getRepositoryResolver().
     */
    public function getModelResolver()
    {
        return $this->getRepositoryResolver();
    }

    /**
     * Method to set property modelResolver
     *
     * @param   RepositoryResolver $repositoryResolver
     *
     * @return  static  Return self to support chaining.
     *
     * @deprecated Use setRepositoryResolver().
     */
    public function setModelResolver($repositoryResolver)
    {
        return $this->setRepositoryResolver($repositoryResolver);
    }

    /**
     * Method to get property ViewResolver
     *
     * @return  ViewResolver
     */
    public function getViewResolver()
    {
        return $this->viewResolver;
    }

    /**
     * Method to set property viewResolver
     *
     * @param   ViewResolver $viewResolver
     *
     * @return  static  Return self to support chaining.
     */
    public function setViewResolver($viewResolver)
    {
        $this->viewResolver = $viewResolver;

        return $this;
    }
}
