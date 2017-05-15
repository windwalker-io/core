<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Database\Traits;

/**
 * The DateFormatTrait class.
 *
 * @since  __DEPLOY_VERSION__
 */
trait DateFormatTrait
{
	/**
	 * getDateFormat
	 *
	 * @return  string
	 */
	public function getDateFormat()
	{
		return $this->db->getQuery(true)->getDateFormat();
	}

	/**
	 * getNullDate
	 *
	 * @return  string
	 */
	public function getNullDate()
	{
		return $this->db->getQuery(true)->getNullDate();
	}
}
