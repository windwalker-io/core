<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2015 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Database\Exporter;

use Windwalker\Core\Database\TableHelper;
use Windwalker\Query\Mysql\MysqlQueryBuilder;

/**
 * The Exporter class.
 * 
 * @since  {DEPLOY_VERSION}
 */
class MysqlExporter extends AbstractExporter
{
	/**
	 * export
	 *
	 * @return mixed|string
	 */
	public function export()
	{
		$tables = $this->db->getDatabase()->getTables(true);

		$sql = array();

		foreach ($tables as $table)
		{
			// Table
			$sql[] = MysqlQueryBuilder::dropTable($table, true);
			$sql[] = $this->getCreateTable($table);

			// Data
			$inserts = $this->getInserts($table);

			if ($inserts)
			{
				$sql[] = $inserts;
			}
		}

		return implode(";\n\n", $sql);
	}

	/**
	 * getCreateTable
	 *
	 * @param $table
	 *
	 * @return array|mixed|string
	 */
	protected function getCreateTable($table)
	{
		$db = $this->db;

		$result = $db->getReader('SHOW CREATE TABLE ' . $this->db->quoteName($table))->loadArray();

		$sql = preg_replace('#AUTO_INCREMENT=\S+#is', '', $result[1]);

		$sql = explode("\n", $sql);

		$tableStriped = TableHelper::stripPrefix($result[0], $db->getPrefix());

		$sql[0] = str_replace($result[0], $tableStriped, $sql[0]);

		$sql = implode("\n", $sql);

		return $sql;
	}

	/**
	 * getInserts
	 *
	 * @param $table
	 *
	 * @return mixed|null|string
	 */
	protected function getInserts($table)
	{
		$db      = $this->db;
		$query   = $db->getQuery(true);
		$iterator   = $db->getReader($query->select('*')->from($table))->getIterator();

		if (!count($iterator))
		{
			return null;
		}

		$sql = array();

		foreach ($iterator as $data)
		{
			$data = (array) $data;

			$data = array_map(
				function($d) use ($query)
				{
					return $query->q($d);
				},
				$data
			);

			$value = implode(', ', $data);

			$sql[] = (string) sprintf("INSERT `%s` VALUES (%s)", $table, $value);
		}

		return (string) implode(";\n", $sql);
	}
}
