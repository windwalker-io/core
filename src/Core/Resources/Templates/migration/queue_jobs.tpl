<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) {YEAR} LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

use Windwalker\Core\Migration\AbstractMigration;
use Windwalker\Database\Schema\Column;
use Windwalker\Database\Schema\DataType;
use Windwalker\Database\Schema\Schema;

/**
 * Migration class, version: {{version}}
 */
class {{className}} extends AbstractMigration
{
	/**
	 * Migrate Up.
	 */
	public function up()
	{
		$this->createTable('queue_jobs', function (Schema $schema)
		{
			$schema->bigint('id')->primary();
			$schema->varchar('queue');
			$schema->longtext('body');
			$schema->tinyint('attempts')->unsigned();
			$schema->datetime('created');
			$schema->datetime('visibility');
			$schema->datetime('reserved')->allowNull();

			$schema->addIndex('queue');
		});
	}

	/**
	 * Migrate Down.
	 */
	public function down()
	{
		$this->drop('queue_jobs');
	}
}
