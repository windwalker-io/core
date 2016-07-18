<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Controller\Traits;

use Windwalker\Core\Controller\Middleware\CsrfProtectionMiddleware;
use Windwalker\Utilities\Queue\PriorityQueue;

/**
 * The CsrfProtectionTrait class.
 *
 * @since  3.0
 */
trait CsrfProtectionTrait
{
	/**
	 * bootCsrfProtectionTrait
	 *
	 * @return  void
	 */
	public function bootCsrfProtectionTrait()
	{
		$this->addMiddleware(CsrfProtectionMiddleware::class, PriorityQueue::HIGH);
	}
}
