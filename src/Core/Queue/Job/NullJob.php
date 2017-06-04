<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Queue\Job;

/**
 * The NullJob class.
 *
 * @since  __DEPLOY_VERSION__
 */
class NullJob implements JobInterface
{
	/**
	 * getName
	 *
	 * @return  string
	 */
	public function getName()
	{
		return 'null';
	}

	public function execute()
	{

	}
}
