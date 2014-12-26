<?php
/**
 * Part of formosa project. 
 *
 * @copyright  Copyright (C) 2011 - 2014 SMS Taiwan, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Windwalker\Core\View\Helper;

use Joomla\Date\Date;
use Joomla\DateTime\DateTime;
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
	 * @param string $package
	 *
	 * @return array
	 */
	public static function getGlobalVariables($package = null)
	{
		if (!static::$flashes)
		{
			static::$flashes = Ioc::getSession()->getFlashBag()->takeAll();
		}

		return array(
			'uri' => Ioc::get('uri'),
			'app' => Ioc::getApplication(),
			'container' => Ioc::factory($package),
			'helper' => new HelperSet,
			'flashes' => static::$flashes,
			'datetime' => new DateTime('now', new \DateTimeZone(Ioc::getConfig()->get('system.timezone', 'UTC')))
		);
	}

	/**
	 * getId
	 *
	 * @param \Windwalker\Data\Data $view
	 *
	 * @return  string
	 */
	public function getId($view)
	{
		return $view->name;
	}

	/**
	 * getClass
	 *
	 * @param \Windwalker\Data\Data $view
	 *
	 * @return  string
	 */
	public function getClass($view)
	{
		return 'view-' . $view->name . ' ' . 'layout-' . $view->layout;
	}

	/**
	 * showFlash
	 *
	 * @param array $flashes
	 *
	 * @return  string
	 */
	public static function showFlash($flashes)
	{
		if (! $flashes)
		{
			return '';
		}

		$html = '<div class="message-wrap">';

		foreach ((array) $flashes as $type => $typeBag)
		{
			$html .= '<div class="alert alert-' . $type . ' alert-dismissible" role="alert">
						<button type="button" class="close" data-dismiss="alert">
						  <span aria-hidden="true">&times;</span>
						  <span class="sr-only">Close</span>
						</button>';

			foreach ((array) $typeBag as $msg)
			{
				$html .= '<p>' . $msg . '</p>';
			}

			$html .= '</div>';
		}

		$html .= '</div>';

		return $html;
	}
}
