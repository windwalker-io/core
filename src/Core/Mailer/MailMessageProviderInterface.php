<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Mailer;

/**
 * The MailMessageProviderInterface class.
 *
 * @since  {DEPLOY_VERSION}
 */
interface MailMessageProviderInterface
{
	/**
	 * render
	 *
	 * @return  MailMessage
	 */
	public static function render();
}
