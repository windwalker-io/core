<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Mailer;

/**
 * The MailMessageProviderInterface class.
 *
 * @since  3.0.1
 */
interface MessageProviderInterface
{
	/**
	 * render
	 *
	 * @return  MailMessage
	 */
	public static function render();
}
