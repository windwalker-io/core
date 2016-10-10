<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Controller\Traits;

use Windwalker\Core\Controller\Middleware\TransactionMiddleware;

/**
 * The TransactionalTrait class.
 *
 * @since  3.0
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
