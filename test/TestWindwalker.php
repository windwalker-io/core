<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Test;

use Windwalker\Core\Windwalker;
use Windwalker\Registry\Registry;

/**
 * The TestWindwalker class.
 *
 * @since  2.1.1
 */
class TestWindwalker extends Windwalker
{
	/**
	 * Prepare system path.
	 *
	 * Write your custom path to $config['path.xxx'].
	 *
	 * @param   Registry  $config  The config registry object.
	 *
	 * @return  void
	 */
	public static function prepareSystemPath(Registry $config)
	{
		$config['path.root']       = WINDWALKER_ROOT;
		$config['path.bin']        = WINDWALKER_BIN;
		$config['path.cache']      = WINDWALKER_CACHE;
		$config['path.etc']        = WINDWALKER_ETC;
		$config['path.logs']       = WINDWALKER_LOGS;
		$config['path.resources']  = WINDWALKER_RESOURCES;
		$config['path.source']     = WINDWALKER_SOURCE;
		$config['path.temp']       = WINDWALKER_TEMP;
		$config['path.templates']  = WINDWALKER_TEMPLATES;
		$config['path.vendor']     = WINDWALKER_VENDOR;
		$config['path.public']     = WINDWALKER_PUBLIC;
		$config['path.migrations'] = WINDWALKER_MIGRATIONS;
		$config['path.seeders']    = WINDWALKER_SEEDERS;
		$config['path.languages']  = WINDWALKER_LANGUAGES;
	}
}
