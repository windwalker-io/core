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
use Windwalker\Filter\OutputFilter;

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
	 * @throws \LogicException
	 * @throws \UnexpectedValueException
	 */
	public function onMailerBeforeSend(Event $event)
	{
		if (!class_exists(CssToInlineStyles::class))
		{
			throw new \LogicException('Please install "tijsverkoyen/css-to-inline-styles": "~2.0" first.');
		}

		/** @var MailMessage $message */
		$message= $event['message'];

		$body = $message->getBody();
		
		preg_match_all('/<style.*?>(.*?)<\/style>/s', $body, $matches, PREG_SET_ORDER);
//		preg_match_all('/<link.*?href="(.*?)"/s', $body, $linkMatches, PREG_SET_ORDER);

		// Remove script & style
		$body = OutputFilter::stripScript($body);
		$body = OutputFilter::stripStyle($body);
		$body = OutputFilter::stripLinks($body);

		/** @var AssetManager $asset */
		$asset= Asset::getInstance();

		$css = '';

		// Loop outside styles
		foreach ($asset->getStyles() as $style)
		{
			$path = $asset->addSysPath($style['url']);

			$css .= file_get_contents($path) . "\n";
		}

		// Loop internal styles
		foreach ($matches as $match)
		{
			if (!isset($match[1]))
			{
				continue;
			}

			$css .= $match[1] . "\n";
		}

		$message->body((new CssToInlineStyles)->convert($body, $css));
	}
}
