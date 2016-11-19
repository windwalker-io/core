<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Controller\Middleware;

/**
 * The TranslationMiddleware class.
 *
 * @since  3.0
 */
class TransactionMiddleware extends AbstractControllerMiddleware
{
	/**
	 * Call next middleware.
	 *
	 * @param   ControllerData $data
	 *
	 * @return mixed
	 *
	 * @throws \Exception
	 * @throws \Throwable
	 */
	public function execute($data = null)
	{
		$data->model->transactionStart(true);

		try
		{
			$result = $this->next->execute($data);
		}
		catch (\Exception $e)
		{
			$data->model->transactionRollback(true);

			throw $e;
		}
		catch (\Throwable $e)
		{
			$data->model->transactionRollback(true);

			throw $e;
		}

		$data->model->transactionCommit(true);

		return $result;
	}
}
