<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Model\Traits;

use Windwalker\Core\Package\Resolver\DataMapperResolver;
use Windwalker\Core\Package\Resolver\RecordResolver;
use Windwalker\Core\DataMapper\CoreDataMapper;
use Windwalker\Core\Mvc\MvcHelper;
use Windwalker\DataMapper\DataMapper;
use Windwalker\Record\Record;

/**
 * The PhoenixModelTrait class.
 *
 * @since  {DEPLOY_VERSION}
 */
trait DatabaseRepositoryTrait
{
	use DatabaseModelTrait;

	/**
	 * Default Record name
	 *
	 * @var  string
	 */
	protected $record;

	/**
	 * Default DataMapper name.
	 *
	 * @var  string
	 */
	protected $dataMapper;

	/**
	 * bootModelRepositoryTrait
	 *
	 * @return  void
	 */
	public function bootModelRepositoryTrait()
	{
		$this->table = property_exists($this, 'table') ? $this->table : null;
		$this->keys = property_exists($this, 'keys') ? $this->keys : 'id';
	}
	
	/**
	 * getRecord
	 *
	 * @param   string $name
	 *
	 * @return  Record
	 */
	public function getRecord($name = null)
	{
		$name = $name ? : $this->record;
		$name = $name ? : $this->getName();

		$mapper = $this->getDataMapper();
		$errors = [];

		if ($mapper instanceof CoreDataMapper)
		{
			$mapper = $mapper->getInstance();
		}

		// (1): Find object from registered namespaces
		$record = RecordResolver::create($name, $this->table, $this->keys, $mapper);

		if ($record)
		{
			return $record;
		}

		$errors[] = sprintf('Record: "%s" not found from namespaces: (%s)', $name, implode(" |\n ", RecordResolver::dumpNamespaces()));

		// (2): Find from package directory.
		$class = sprintf('%s\Record\%sRecord', MvcHelper::getPackageNamespace(get_called_class(), 2), ucfirst($name));

		if (class_exists($class))
		{
			return new $class;
		}

		$errors[] = sprintf('Class: %s not exists.', $class);

		if ($name)
		{
			throw new \LogicException(implode("\n- ", $errors));
		}

		if (!$this->table)
		{
			throw new \LogicException('Please add table property to ' . get_called_class() . " to support Record object. \n" . implode("\n- ", $errors));
		}

		// (3): If name is null, we get default object with table name provided.
		return new Record($this->table, $this->keys, $mapper);
	}

	/**
	 * getDataMapper
	 *
	 * @param string $name
	 *
	 * @return  DataMapper
	 */
	public function getDataMapper($name = null)
	{
		$name = $name ? : $this->dataMapper;
		$name = $name ? : $this->getName();

		// (1): Find object from registered namespaces
		$mapper = DataMapperResolver::create($name, $this->table, $this->keys, $this->db);
		$errors = [];

		if ($mapper)
		{
			return $mapper;
		}

		$errors[] = sprintf('DataMapper: "%s" not found from namespaces: (%s)', $name, implode(" |\n ", DataMapperResolver::dumpNamespaces()));

		// (2): Find from package directory.
		$class = sprintf('%s\DataMapper\%sMapper', MvcHelper::getPackageNamespace(get_called_class(), 2), ucfirst($name));

		if (class_exists($class))
		{
			return new $class($this->db);
		}

		$errors[] = sprintf('Class: %s not exists.', $class);

		if ($name)
		{
			throw new \LogicException(implode("\n- ", $errors));
		}

		if (!$this->table)
		{
			throw new \LogicException('Please add table property to ' . get_called_class() . " to support Record object. \n" . implode("\n- ", $errors));
		}

		// (3): If name is null, we get default object with table name provided.
		return new DataMapper($this->table, $this->keys, $this->db);
	}
}
