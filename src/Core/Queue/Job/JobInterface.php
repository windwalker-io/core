<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Queue\Job;

/**
 * The AbstractJob class.
 *
 * @since  __DEPLOY_VERSION__
 */
interface JobInterface
{
	/**
	 * getName
	 *
	 * @return  string
	 */
	public function getName();

	public function execute();
}
