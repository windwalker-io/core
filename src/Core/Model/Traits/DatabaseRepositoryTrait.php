<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Model\Traits;

use Windwalker\Core\Database\NullDataMapper;
use Windwalker\Core\Database\NullRecord;
use Windwalker\Core\Mvc\MvcHelper;
use Windwalker\Core\Package\Resolver\DataMapperResolver;
use Windwalker\Core\Package\Resolver\RecordResolver;
use Windwalker\DataMapper\AbstractDatabaseMapperProxy;
use Windwalker\DataMapper\DataMapper;
use Windwalker\Record\Record;

/**
 * The DatabaseRepositoryTrait class.
 *
 * @since  3.0
 */
trait DatabaseRepositoryTrait
{
    use DatabaseModelTrait;

    /**
     * bootModelRepositoryTrait
     *
     * @return  void
     */
    public function bootDatabaseRepositoryTrait()
    {
        $this->table      = property_exists($this, 'table') ? $this->table : null;
        $this->keys       = property_exists($this, 'keys') ? $this->keys : 'id';
        $this->record     = property_exists($this, 'record') ? $this->record : null;
        $this->dataMapper = property_exists($this, 'dataMapper') ? $this->dataMapper : null;
    }

    /**
     * getRecord
     *
     * @param   string $name
     *
     * @return  Record
     * @throws \LogicException
     */
    public function getRecord($name = null)
    {
        $recordName = $name ?: $this->record;

        if ($recordName === false) {
            return new NullRecord;
        }

        $recordName = $recordName ?: $this->getName();

        $mapper = $this->getDataMapper($name);

        if ($mapper instanceof AbstractDatabaseMapperProxy) {
            $mapper = $mapper->getInstance();
        }

        // (1) If name is class, just new it.
        if (class_exists($recordName)) {
            return new $recordName($this->table, $this->keys, $mapper);
        }

        // (2): Find object from registered namespaces
        if ($record = RecordResolver::create($recordName, $this->table, $this->keys, $mapper)) {
            return $record;
        }

        $errors[] = sprintf('Record: "%s" not found from namespaces: (%s)', $recordName,
            implode(" |\n ", RecordResolver::dumpNamespaces()));

        // (3): Find from package directory.
        $class = sprintf('%s\Record\%sRecord', MvcHelper::getPackageNamespace(get_called_class(), 2),
            ucfirst($recordName));

        if (class_exists($class)) {
            return new $class($this->table, $this->keys, $mapper);
        }

        $errors[] = sprintf('Class: %s not exists.', $class);

        // If name is NULL and this model prepared a default record name.
        // We must throw exception to tell developers record not found.
        if (!$name && $this->record) {
            throw new \LogicException(implode("\n- ", $errors));
        }

        // If name not NULL, set it as table name, otherwise use pre-set table property.
        $table = $name ?: $this->table;

        if (!$table) {
            throw new \LogicException('Please add table property to ' . get_called_class() . " to support Record object. \n" . implode("\n- ",
                    $errors));
        }

        // (4): If name is null, we get default object with table name provided.
        return new Record($table, $this->keys, $mapper);
    }

    /**
     * getDataMapper
     *
     * @param string $name
     *
     * @return  DataMapper
     * @throws \LogicException
     */
    public function getDataMapper($name = null)
    {
        $mapperName = $name ?: $this->dataMapper;

        if ($mapperName === false) {
            return new NullDataMapper;
        }

        $mapperName = $mapperName ?: $this->getName();

        // (1) If name is class, just new it.
        if (class_exists($mapperName)) {
            return new $mapperName($this->table, $this->keys, $this->db);
        }

        // (2): Find object from registered namespaces
        if ($mapper = DataMapperResolver::create($mapperName, $this->table, $this->keys, $this->db)) {
            return $mapper;
        }

        $errors[] = sprintf('DataMapper: "%s" not found from namespaces: (%s)', $mapperName,
            implode(" |\n ", DataMapperResolver::dumpNamespaces()));

        // (3): Find from package directory.
        $class = sprintf('%s\DataMapper\%sMapper', MvcHelper::getPackageNamespace(get_called_class(), 2),
            ucfirst($mapperName));

        if (class_exists($class)) {
            return new $class($this->table, $this->keys, $this->db);
        }

        $errors[] = sprintf('Class: %s not exists.', $class);

        // If name is NULL and this model prepared a default mapper name.
        // We must throw exception to tell developers mapper not found.
        if (!$name && $this->dataMapper) {
            throw new \LogicException(implode("\n- ", $errors));
        }

        // If name not NULL, set it as table name, otherwise use pre-set table property.
        $table = $name ?: $this->table;

        if (!$table) {
            throw new \LogicException('Please add table property to ' . get_called_class() . " to support DataMapper object. \n" . implode("\n- ",
                    $errors));
        }

        // (4): If name is null, we get default object with table name provided.
        return new DataMapper($table, $this->keys, $this->db);
    }

    /**
     * getTableName
     *
     * @return  string
     */
    public function getTableName()
    {
        return isset($this->table) ? $this->table : $this->getRecord()->getTableName();
    }

    /**
     * getKeyName
     *
     * @param bool $multiple
     *
     * @return  array|string
     *
     * @throws \LogicException
     */
    public function getKeyName($multiple = false)
    {
        if (isset($this->keys)) {
            $keys = (array) $this->keys;

            if ($multiple) {
                return $keys;
            }

            return $keys[0];
        }

        return $this->getRecord()->getKeyName($multiple);
    }
}
