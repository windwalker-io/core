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
	 * bootModelRepositoryTrait
	 *
	 * @return  void
	 */
	public function bootModelRepositoryTrait()
	{
		$this->table  = property_exists($this, 'table')  ? $this->table  : null;
		$this->keys   = property_exists($this, 'keys')   ? $this->keys   : 'id';
		$this->record = property_exists($this, 'record') ? $this->record : null;
		$this->dataMapper = property_exists($this, 'dataMapper') ? $this->dataMapper : null;
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
		$recordName = $name ? : $this->record;
		$recordName = $recordName ? : $this->getName();

		$mapper = $this->getDataMapper();

		if ($mapper instanceof CoreDataMapper)
		{
			$mapper = $mapper->getInstance();
		}

		// (1): Find object from registered namespaces
		if ($record = RecordResolver::create($recordName, $this->table, $this->keys, $mapper))
		{
			return $record;
		}

		$errors[] = sprintf('Record: "%s" not found from namespaces: (%s)', $recordName, implode(" |\n ", RecordResolver::dumpNamespaces()));

		// (2): Find from package directory.
		$class = sprintf('%s\Record\%sRecord', MvcHelper::getPackageNamespace(get_called_class(), 2), ucfirst($recordName));

		if (class_exists($class))
		{
			return new $class;
		}

		$errors[] = sprintf('Class: %s not exists.', $class);

		if ($recordName)
		{
			throw new \LogicException(implode("\n- ", $errors));
		}

		// If name not NULL, set it as table name, otherwise use pre-set table property.
		$table = $name ? : $this->table;

		if (!$table)
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
		$mapperName = $name ? : $this->dataMapper;
		$mapperName = $mapperName ? : $this->getName();

		// (1): Find object from registered namespaces
		if ($mapper = DataMapperResolver::create($mapperName, $this->table, $this->keys, $this->db))
		{
			return $mapper;
		}

		$errors[] = sprintf('DataMapper: "%s" not found from namespaces: (%s)', $mapperName, implode(" |\n ", DataMapperResolver::dumpNamespaces()));

		// (2): Find from package directory.
		$class = sprintf('%s\DataMapper\%sMapper', MvcHelper::getPackageNamespace(get_called_class(), 2), ucfirst($mapperName));

		if (class_exists($class))
		{
			return new $class($this->db);
		}

		$errors[] = sprintf('Class: %s not exists.', $class);

		if (!$mapperName)
		{
			throw new \LogicException(implode("\n- ", $errors));
		}

		// If name not NULL, set it as table name, otherwise use pre-set table property.
		$table = $name ? : $this->table;

		if (!$table)
		{
			throw new \LogicException('Please add table property to ' . get_called_class() . " to support Record object. \n" . implode("\n- ", $errors));
		}

		// (3): If name is null, we get default object with table name provided.
		return new DataMapper($table, $this->keys, $this->db);
	}
}
