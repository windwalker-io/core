<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Message;

use Windwalker\Core\Ioc;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Provider\RendererProvider;
use Windwalker\Core\Widget\WidgetManager;

/**
 * The MessageHelper class.
 *
 * @since  __DEPLOY_VERSION__
 */
class MessageHelper
{
	/**
	 * Property messages.
	 *
	 * @var  array
	 */
	protected static $messages = null;

	/**
	 * render
	 *
	 * @param WidgetManager        $widget
	 * @param string               $template
	 * @param array                $messages
	 * @param string               $engine
	 * @param AbstractPackage|null $package
	 *
	 * @return  string
	 */
	public static function render(WidgetManager $widget, $template = 'windwalker.message.default', array $messages = null, $engine = 'php', AbstractPackage $package = null)
	{
		$messages = $messages === null ? static::getMessages(true) : $messages;

		return $widget->render($template, ['messages' => $messages], $engine, $package);
	}

	/**
	 * getMessages
	 *
	 * @param bool $clear
	 *
	 * @return array
	 */
	public static function getMessages($clear = true)
	{
		if (static::$messages === null)
		{
			static::$messages = Ioc::getApplication()->getMessages($clear);
		}

		return static::$messages;
	}
}
