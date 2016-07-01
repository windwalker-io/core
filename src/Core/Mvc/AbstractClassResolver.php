<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Mvc;

use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\PackageAwareTrait;
use Windwalker\DI\Container;
use Windwalker\DI\ContainerAwareInterface;
use Windwalker\DI\ContainerAwareTrait;
use Windwalker\String\StringNormalise;
use Windwalker\Utilities\Queue\PriorityQueue;

/**
 * The AbstractResolver class.
 *
 * @since  {DEPLOY_VERSION}
 */
abstract class AbstractClassResolver implements ClassResolverInterface, ContainerAwareInterface
{
	use ContainerAwareTrait;
	use PackageAwareTrait;

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
	 * Property baseClass.
	 *
	 * @var  string
	 */
	protected $baseClass;

	/**
	 * ControllerResolver constructor.
	 *
	 * @param AbstractPackage $package
	 * @param Container       $container
	 * @param array           $namespaces
	 */
	public function __construct(AbstractPackage $package, Container $container = null, $namespaces = array())
	{
		$this->container = $container;
		$this->package   = $package;

		$this->setNamespaces($namespaces);
	}

	/**
	 * Resolve class path.
	 *
	 * @param   string $name
	 *
	 * @return  string
	 *
	 * @throws \UnexpectedValueException
	 */
	public function resolve($name)
	{
		if (class_exists($name))
		{
			return $name;
		}

		$name = static::normalise($name);

		$namespaces = clone $this->namespaces;

		$this->registerDefaultNamespace($namespaces);

		foreach (clone $namespaces as $ns)
		{
			$class = $ns . '\\' . $name;

			if (class_exists($class = $this->resolveClassAlias($class)))
			{
				if ($this->baseClass && !is_subclass_of($class, $this->baseClass))
				{
					throw new \UnexpectedValueException(sprintf(
						'Class: "%s" should be sub class of %s',
						$this->baseClass,
						$class
					));
				}

				return $class;
			}
		}

		throw new \UnexpectedValueException(sprintf(
			'Can not find any classes with name: "%s" in package: "%s", namespaces: ( %s ).',
			$name,
			$this->package->getName(),
			implode(" |\n ", $namespaces->toArray())
		));
	}

	/**
	 * create
	 *
	 * @param string $name
	 * @param array  ...$args
	 *
	 * @return  object
	 *
	 * @throws \UnexpectedValueException
	 */
	public function create($name, ...$args)
	{
		$class = $this->resolve($name);

		return new $class(...$args);
	}

	/**
	 * If didn't found any exists class, fallback to default class which in current package..
	 *
	 * @return string Found class name.
	 */
	abstract protected function getDefaultNamespace();

	/**
	 * registerDefaultNamespace
	 *
	 * @param PriorityQueue $namespaces
	 * @param int           $priority
	 *
	 * @return  static
	 */
	protected function registerDefaultNamespace(PriorityQueue $namespaces, $priority = PriorityQueue::NORMAL)
	{
		$namespaces->insert($this->getDefaultNamespace(), $priority);

		return $this;
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
	public function addNamespace($namespace, $priority = PriorityQueue::NORMAL)
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
		$this->setClassAliases([]);

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
}
