<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Mvc;

use Windwalker\Core\Ioc;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\PackageResolver;
use Windwalker\DI\Container;
use Windwalker\String\StringHelper;
use Windwalker\String\StringNormalise;
use Windwalker\Utilities\Queue\Priority;
use Windwalker\Utilities\Queue\PriorityQueue;
use Windwalker\Utilities\Reflection\ReflectionHelper;

/**
 * The AbstractResolver class.
 *
 * @since  {DEPLOY_VERSION}
 */
abstract class AbstractClassResolver implements ClassResolverInterface
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
	 * Resolve class path.
	 *
	 * @param   string|AbstractPackage $package
	 * @param   string                 $name
	 *
	 * @return  string|false
	 */
	public function resolve($package, $name)
	{
		if (class_exists($name))
		{
			return $name;
		}

		$pkg = $this->splitPackage($name);

		if ($pkg)
		{
			$package = $pkg;
		}

		if (!$package instanceof AbstractPackage)
		{
			$package = $this->getPackageResolver()->getPackage($package);
		}

		$name = static::normalise($name);

		if (!$class = $this->find($name))
		{
			$namespace = ReflectionHelper::getNamespaceName($package);

			$class = $this->getDefaultClass($namespace, $name);
		}

		if (!class_exists($class = $this->resolveClassAlias($class)))
		{
			return false;
		}

		return $class;
	}

	/**
	 * If didn't found any exists class, fallback to default class which in current package..
	 *
	 * @param   string  $namespace  The package namespace.
	 * @param   string  $name       The class task name.
	 *
	 * @return  string  Found class name.
	 */
	abstract protected function getDefaultClass($namespace, $name);

	/**
	 * findController
	 *
	 * @param   string $name
	 *
	 * @return  string|false
	 */
	public function find($name)
	{
		foreach (clone $this->namespaces as $ns)
		{
			$class = $ns . '\\' . $name;

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

		return strtolower(static::getPrefix() . '.' . $name);
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
	 * reset
	 *
	 * @return  static
	 */
	public function reset()
	{
		$this->setNamespaces(new PriorityQueue);
		$this->setClassAliases(array());

		return $this;
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
