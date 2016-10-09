<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Mailer\Listener;

use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use Windwalker\Core\Asset\Asset;
use Windwalker\Core\Asset\AssetManager;
use Windwalker\Core\Mailer\MailMessage;
use Windwalker\Event\Event;

/**
 * The MailInlinerListener class.
 *
 * @since  {DEPLOY_VERSION}
 */
class MailInlinerListener
{
	/**
	 * onMailerBeforeSend
	 *
	 * @param Event $event
	 *
	 * @return  void
	 * @throws \UnexpectedValueException
	 */
	public function onMailerBeforeSend(Event $event)
	{
		/** @var MailMessage $message */
		$message= $event['message'];

		$body = $message->getBody();

		$inliner = new CssToInlineStyles;

		/** @var AssetManager $asset */
		$asset= Asset::getInstance();

		$css = '';

		foreach ($asset->getStyles() as $style)
		{
			$style = $asset->addSysPath($asset->path($style));
show($style);
			$css .= file_get_contents($style) . "\n";
		}
show($css);
		exit(' @Checkpoint');
		foreach ($asset->getInternalStyles() as $internalStyle)
		{
			$css .= $internalStyle . "\n";
		}
	}
}
