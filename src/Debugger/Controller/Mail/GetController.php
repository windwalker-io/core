<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Debugger\Controller\Mail;

use Windwalker\Core\Controller\AbstractController;
use Windwalker\Core\Mailer\Adapter\DebugMailerAdapter;
use Windwalker\Core\Mailer\Adapter\MailerAdapterInterface;
use Windwalker\Core\Mailer\Mailer;
use Windwalker\Core\Mailer\MailMessage;

/**
 * The GetController class.
 *
 * @since  {DEPLOY_VERSION}
 */
class GetController extends AbstractController
{
	/**
	 * Do execute action.
	 *
	 * @return  mixed
	 *
	 * @throws \Exception
	 */
	protected function doExecute()
	{
		$class = $this->input->getString('class');

		$view = $this->getView();

		if ($class && is_subclass_of($class, MailMessage::class))
		{
			Mailer::getContainer()
				->prepareSharedObject(DebugMailerAdapter::class)
				->alias(MailerAdapterInterface::class, DebugMailerAdapter::class);

			/** @var MailMessage $message */
			$message = Mailer::send($class::create());

			$view['message'] = $message;
		}

		$view['class'] = $class;

		return $view->render();
	}
}
