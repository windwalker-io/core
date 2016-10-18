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

/**
 * The GetController class.
 *
 * @since  3.1
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

			// Set default sender
			if (!$message->getFrom())
			{
				$config = $this->app->config;

				if ($config->exists('mail.from.email'))
				{
					$message->from($config->get('mail.from.email'), $config->get('mail.from.name'));
				}
			}
		}

		$view['class'] = $class;

		return $view->render();
	}
}
