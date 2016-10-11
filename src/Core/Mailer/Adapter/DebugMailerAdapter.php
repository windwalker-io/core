<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Mailer\Adapter;

use Windwalker\Core\Mailer\MailMessage;

/**
 * The DebugMailerAdapter class.
 *
 * @since  3.0.1
 */
class DebugMailerAdapter implements MailerAdapterInterface
{
	/**
	 * send
	 *
	 * @param MailMessage $message
	 *
	 * @return  MailMessage
	 */
	public function send(MailMessage $message)
	{
		return $message;
	}
}
