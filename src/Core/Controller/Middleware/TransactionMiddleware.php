<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Controller\Middleware;

/**
 * The TranslationMiddleware class.
 *
 * @since  {DEPLOY_VERSION}
 */
class TransactionMiddleware extends AbstractControllerMiddleware
{
	/**
	 * Call next middleware.
	 *
	 * @param   ControllerData $data
	 *
	 * @return  mixed
	 */
	public function execute($data = null)
	{
		$data->model->transactionStart(true);

		try
		{
			$result = $this->next->execute($data);
		}
		finally
		{
			$data->model->transactionRollback(true);
		}

		$data->model->transactionCommmit(true);

		return $result;
	}
}
