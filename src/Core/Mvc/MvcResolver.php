<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Mvc;

use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Utilities\Queue\Priority;

/**
 * The MvcResolver class. This is a composite class to wrap Controller, Model, View resolvers.
 *
 * @since  {DEPLOY_VERSION}
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
	 * Property modelResolver.
	 *
	 * @var  ModelResolver
	 */
	protected $modelResolver;

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
			new ModelResolver($package, $package->getContainer()),
			new ViewResolver($package, $package->getContainer())
		);
	}

	/**
	 * MvcResolver constructor.
	 *
	 * @param ControllerResolver $controllerResolver
	 * @param ModelResolver      $modelResolver
	 * @param ViewResolver       $viewResolver
	 */
	public function __construct(ControllerResolver $controllerResolver, ModelResolver $modelResolver, ViewResolver $viewResolver)
	{
		$this->controllerResolver = $controllerResolver;
		$this->modelResolver      = $modelResolver;
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
	public function resolveModel($package, $name)
	{
		return $this->modelResolver->resolve($name);
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
	public function addNamespace($namespace, $priority = Priority::NORMAL)
	{
		$this->controllerResolver->addNamespace($namespace . '\Controller', $priority);
		$this->modelResolver->addNamespace($namespace . '\Model', $priority);
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
		$this->modelResolver->reset();
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
	 * @return  ModelResolver
	 */
	public function getModelResolver()
	{
		return $this->modelResolver;
	}

	/**
	 * Method to set property modelResolver
	 *
	 * @param   ModelResolver $modelResolver
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setModelResolver($modelResolver)
	{
		$this->modelResolver = $modelResolver;

		return $this;
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
