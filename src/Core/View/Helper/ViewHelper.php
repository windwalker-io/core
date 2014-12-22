<?php
/**
 * Part of formosa project. 
 *
 * @copyright  Copyright (C) 2011 - 2014 SMS Taiwan, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Windwalker\Core\View\Helper;

use Joomla\Date\Date;
use Windwalker\Core\Ioc;
use Windwalker\Core\View\Helper\Set\HelperSet;
use Windwalker\Core\View\HtmlView;

/**
 * Class RendererHelper
 *
 * @since 1.0
 */
class ViewHelper extends AbstractHelper
{
	/**
	 * Property flashes.
	 *
	 * @var  array
	 */
	protected static $flashes = array();

	/**
	 * getGlobalVariables
	 *
	 * @return  array
	 */
	public static function getGlobalVariables(HtmlView $view)
	{
		$package = $view->getPackage();

		if (!static::$flashes)
		{
			static::$flashes = Ioc::getSession()->getFlashBag()->takeAll();
		}

		$globals = array(
			'uri' => Ioc::get('uri'),
			'app' => Ioc::getApplication(),
			'container' => Ioc::getContainer($package->getName()),
			'helper' => new HelperSet,
			'flashes' => static::$flashes,
			'datetime' => new Date('now', new \DateTimeZone(Ioc::getConfig()->get('system.timezone', 'UTC')))
		);

		foreach ($globals as $key => $value)
		{
			$view->set($key, $value);
		}
	}
}
