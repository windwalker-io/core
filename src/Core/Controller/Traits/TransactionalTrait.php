<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Controller\Traits;

use Windwalker\Core\Controller\Middleware\TransactionMiddleware;

/**
 * The TransactionalTrait class.
 *
 * @since  {DEPLOY_VERSION}
 */
trait TransactionalTrait
{
	/**
	 * bootTranslationableTrait
	 *
	 * @return  void
	 */
	public function bootTransactionalTrait()
	{
		$this->addMiddleware(TransactionMiddleware::class);
	}
}
