<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Core\View\Helper;

use Windwalker\Core\Package\AbstractPackage;

/**
 * Class ViewHelper
 *
 * @since 1.0
 *
 * @deprecated
 */
class ViewHelper extends AbstractHelper
{
	/**
	 * Property flashes.
	 *
	 * @var  array
	 */
	protected static $messages = [];

	/**
	 * getGlobalVariables
	 *
	 * @param AbstractPackage $package
	 *
	 * @return array
	 */
	public static function getGlobalVariables(AbstractPackage $package = null)
	{
		if (!static::$messages)
		{
			static::$messages = $package->getContainer()
				->get('session')
				->getFlashBag()
				->takeAll();
		}

		$container = $package->getContainer();

		return [
			'uri'        => $container->get('uri'),
			'app'        => $container->get('application'),
			'package'    => $package,
			'router'     => $package->router,
			'asset'      => $container->get('asset'),
			'messages'   => static::$messages,
			'translator' => $container->get('language'),
			'datetime'   => new \DateTime('now', new \DateTimeZone($container->get('config')->get('system.timezone', 'UTC')))
		];
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
			$route = $package->getName() . '@' . $route;
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
