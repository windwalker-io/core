<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Mailer;

use Windwalker\Core\Facade\AbstractProxyFacade;
use Windwalker\Core\Mailer\Adapter\MailerAdapterInterface;

/**
 * The Mailer class.
 *
 * @see  MailerManager
 * @see  MailMessage
 * @see  MailAttachment
 *
 * @method  static  MailMessage             createMessage($subject = null, $content = null, $html = true)
 * @method  static  boolean|callable        send($message)
 * @method  static  MailerManager           setMessageClass($messageClass)
 * @method  static  MailerAdapterInterface  getAdapter()
 * @method  static  MailerManager           setAdapter(MailerAdapterInterface $adapter)
 *
 * @since  3.0
 */
class Mailer extends AbstractProxyFacade
{
	/**
	 * Property _key.
	 *
	 * @var  string
	 */
	protected static $_key = 'mailer';
}
