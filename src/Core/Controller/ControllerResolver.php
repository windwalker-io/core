<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Controller;

use Windwalker\Core\Ioc;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\PackageHelper;
use Windwalker\Core\Package\PackageResolver;
use Windwalker\Core\Utilities\Classes\MvcHelper;
use Windwalker\DI\Container;
use Windwalker\Router\Exception\RouteNotFoundException;
use Windwalker\String\StringHelper;
use Windwalker\String\StringNormalise;
use Windwalker\Utilities\Queue\Priority;
use Windwalker\Utilities\Queue\PriorityQueue;
use Windwalker\Utilities\Reflection\ReflectionHelper;

/**
 * The ControllerResolver class.
 *
 * @since  2.1.1
 */
class ControllerResolver
{
	/**
	 * Property container.
	 *
	 * @var  Container
	 */
	protected $container;

	/**
	 * Property namespaces.
	 *
	 * @var  PriorityQueue
	 */
	protected $namespaces = array();

	/**
	 * Property aliases.
	 *
	 * @var  array
	 */
	protected $classAliases = array();

	/**
	 * ControllerResolver constructor.
	 *
	 * @param Container $container
	 * @param array     $namespaces
	 */
	public function __construct(Container $container = null, $namespaces = array())
	{
		$this->container = $container ? : $this->getContainer();

		$this->setNamespaces($namespaces);
	}

	/**
	 * getController
	 *
	 * @param   AbstractPackage|string  $package
	 * @param   string                  $controller
	 *
	 * @return  string
	 */
	public function resolveController($package, $controller)
	{
		$pkg = $this->splitPackage($controller);

		if ($pkg)
		{
			$package = $pkg;
		}

		if (!$package instanceof AbstractPackage)
		{
			$package = $this->getPackageResolver()->getPackage($package);
		}

		$controller = static::normalise($controller);

		if (!$class = $this->findController($controller))
		{
			$namespace = ReflectionHelper::getNamespaceName($package);

			$class = $namespace . '\Controller\\' . $controller;
		}

		if (!class_exists($class = $this->resolveClassAlias($class)))
		{
			$namespaces = $this->dumpNamespaces();

			$namespaces[] = $namespace = ReflectionHelper::getNamespaceName($package);

			throw new RouteNotFoundException('Controller: ' . $controller . ' not found. Namespaces: ' . implode(', ', $namespaces), 404);
		}

		return $class;
	}

	/**
	 * getController
	 *
	 * @param   AbstractPackage|string  $package
	 * @param   string                  $controller
	 *
	 * @return  string
	 *
	 * @deprecated  Use resolveController() instead.
	 */
	public static function getController($package, $controller)
	{
		return Ioc::get('controller.resolver')->resolveController($package, $controller);
	}

	/**
	 * findController
	 *
	 * @param   string  $controller
	 *
	 * @return  string|false
	 */
	public function findController($controller)
	{
		foreach (clone $this->namespaces as $ns)
		{
			$class = $ns . '\\' . $controller;

			if (class_exists($class = $this->resolveClassAlias($class)))
			{
				return $class;
			}
		}

		return false;
	}

	/**
	 * splitPackage
	 *
	 * @param   string  $name
	 *
	 * @return  string
	 */
	public function splitPackage(&$name)
	{
		list($package, $name) = StringHelper::explode('@', $name, 2, 'array_unshift');

		return $package;
	}

	/**
	 * getDIKey
	 *
	 * @param   string  $name
	 *
	 * @return  string
	 */
	public static function getDIKey($name)
	{
		$name = str_replace(array('/', '\\'), '.', $name);

		$name = StringNormalise::toDotSeparated($name);

		return 'controller.' . $name;
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
		$namespace = static::normalise($namespace);

		$this->namespaces->insert($namespace, $priority);

		return $this;
	}

	/**
	 * Method to get property Namespaces
	 *
	 * @return  PriorityQueue
	 */
	public function getNamespaces()
	{
		return $this->namespaces;
	}

	/**
	 * Method to set property namespaces
	 *
	 * @param   array|PriorityQueue $namespaces
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setNamespaces($namespaces)
	{
		if (!$namespaces instanceof PriorityQueue)
		{
			$namespaces = new PriorityQueue($namespaces);
		}

		$this->namespaces = $namespaces;

		return $this;
	}

	/**
	 * dumpNamespaces
	 *
	 * @return  array
	 */
	public function dumpNamespaces()
	{
		return $this->namespaces->toArray();
	}

	/**
	 * normalise
	 *
	 * @param   string  $name
	 *
	 * @return  string
	 */
	public static function normalise($name)
	{
		$name = str_replace('.', '\\', $name);

		return StringNormalise::toClassNamespace($name);
	}

	/**
	 * resolveClassAlias
	 *
	 * @param   string  $alias
	 *
	 * @return  string
	 */
	public function resolveClassAlias($alias)
	{
		$alias = static::normalise($alias);

		if (isset($this->classAliases[$alias]))
		{
			return $this->classAliases[$alias];
		}

		return $alias;
	}

	/**
	 * addClassAlias
	 *
	 * @param   string  $alias
	 * @param   string  $class
	 *
	 * @return  static
	 */
	public function addClassAlias($alias, $class)
	{
		$alias = static::normalise($alias);
		$class = static::normalise($class);

		$this->classAliases[$alias] = $class;

		return $this;
	}

	/**
	 * Method to get property Aliases
	 *
	 * @return  array
	 */
	public function getClassAliases()
	{
		return $this->classAliases;
	}

	/**
	 * Method to set property aliases
	 *
	 * @param   array $classAliases
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setClassAliases(array $classAliases)
	{
		$this->classAliases = $classAliases;

		return $this;
	}

	/**
	 * getPackageResolver
	 *
	 * @return  PackageResolver
	 */
	public function getPackageResolver()
	{
		return $this->container->get('package.resolver');
	}

	/**
	 * Method to get property Container
	 *
	 * @return  Container
	 */
	public function getContainer()
	{
		if (!$this->container)
		{
			$this->container = Ioc::factory();
		}

		return $this->container;
	}

	/**
	 * Method to set property container
	 *
	 * @param   Container $container
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setContainer($container)
	{
		$this->container = $container;

		return $this;
	}
}
