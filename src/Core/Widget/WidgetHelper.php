<?php
/**
 * Part of Windwalker project. 
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Widget;

/**
 * The WidgetHelper class.
 * 
 * @since  {DEPLOY_VERSION}
 */
abstract class WidgetHelper
{
	const ENGINE_PHP      = 'php';
	const ENGINE_BLADE    = 'blade';
	const ENGINE_TWIG     = 'twig';
	const ENGINE_MUSTACHE = 'mustache';

	/**
	 * render
	 *
	 * @param string $layout
	 * @param array  $data
	 * @param string $type
	 *
	 * @return  string
	 */
	public static function render($layout, $data = array(), $type = self::ENGINE_PHP)
	{
		if ($type == 'php')
		{
			$type = '';
		}

		$class = 'Windwalker\Core\Widget\\' . ucfirst($type) . 'Widget';

		if (!class_exists($class))
		{
			throw new \DomainException('Widget engine: ' . $type . ' not exists.');
		}

		/** @var WidgetInterface $widget */
		$widget = new $class($layout);

		return $widget->render($data);
	}
}
