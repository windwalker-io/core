<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Mailer\Adapter;

use Windwalker\Core\Mailer\MailMessage;

/**
 * The MailerAdapterInterface class.
 *
 * @since  {DEPLOY_VERSION}
 */
interface MailerAdapterInterface
{
	/**
	 * send
	 *
	 * @param MailMessage $message
	 *
	 * @return  boolean
	 */
	public function send(MailMessage $message);
}
