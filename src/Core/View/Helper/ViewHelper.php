<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT Taiwan, Inc. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Core\View\Helper;

use Windwalker\Core\Ioc;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\View\Helper\Set\HelperSet;

/**
 * Class ViewHelper
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
	 * @param AbstractPackage $package
	 *
	 * @return array
	 */
	public static function getGlobalVariables(AbstractPackage $package = null)
	{
		if (!static::$flashes)
		{
			static::$flashes = $package->getContainer()
				->get('session')
				->getFlashBag()
				->takeAll();
		}

		$container = $package->getContainer();

		return array(
			'uri'       => $container->get('uri'),
			'app'       => $container->get('system.application'),
			// 'container' => $container,
			'package'   => $package,
			'router'    => $package->getRouter(),
			'flashes'   => static::$flashes,
			'language'  => $container->get('system.language'),
			'datetime'  => new \DateTime('now', new \DateTimeZone($container->get('config')->get('system.timezone', 'UTC')))
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
	 * isActiveRoute
	 *
	 * @param   string  $route
	 *
	 * @return  bool|string
	 */
	public function isActiveRoute($route)
	{
		$package = $this->getParent()
			->getView()
			->getPackage();

		$config = $package->getContainer()
			->get('config');

		if (count(explode(':', $route)) < 2)
		{
			$route = $package->getName() . ':' . $route;
		}

		return $config->get('route.matched') == $route ? 'active' : false;
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
