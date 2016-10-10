<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Debugger\Controller\Mail;

use Windwalker\Core\Controller\AbstractController;
use Windwalker\Core\Mailer\Adapter\DebugMailerAdapter;
use Windwalker\Core\Mailer\Adapter\MailerAdapterInterface;
use Windwalker\Core\Mailer\Mailer;
use Windwalker\Core\Mailer\MailMessage;
use Windwalker\Core\Mailer\MessageProviderInterface;

/**
 * The GetController class.
 *
 * @since  3.0.1
 */
class GetController extends AbstractController
{
	/**
	 * Do execute action.
	 *
	 * @return  mixed
	 */
	protected function doExecute()
	{
		$class = $this->input->getString('class');

		$view = $this->getView();

		if ($class && is_subclass_of($class, MessageProviderInterface::class))
		{
			Mailer::getContainer()
				->prepareSharedObject(DebugMailerAdapter::class)
				->alias(MailerAdapterInterface::class, DebugMailerAdapter::class);

			/** @var MailMessage $message */
			$message = Mailer::send($class::render());

			$view['message'] = $message;
		}

		$view['class'] = $class;

		return $view->render();
	}
}
