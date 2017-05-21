<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Queue;

/**
 * The AbstractJob class.
 *
 * @since  __DEPLOY_VERSION__
 */
abstract class AbstractJob
{
	/**
	 * getName
	 *
	 * @return  string
	 */
	abstract public function getName();

	abstract public function handle();
}
