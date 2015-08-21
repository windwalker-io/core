<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Core\View\Twig;

use Windwalker\Core\View\Helper\ViewHelper;
use Windwalker\Core\View\HtmlView;
use Windwalker\Data\Data;
use Windwalker\DI\Container;

/**
 * Class WindwalkerExtension
 *
 * @since 1.0
 */
class WindwalkerExtension extends \Twig_Extension
{
	/**
	 * Property container.
	 *
	 * @var  Container
	 */
	protected $container;

	/**
	 * WindwalkerExtension constructor.
	 *
	 * @param Container $container
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * Returns the name of the extension.
	 *
	 * @return string The extension name
	 */
	public function getName()
	{
		return 'windwalker';
	}

	/**
	 * getGlobals
	 *
	 * @return  array
	 */
	public function getGlobals()
	{
		return array();
	}

	/**
	 * getFunctions
	 *
	 * @return  array
	 */
	public function getFunctions()
	{
		return array(
			new \Twig_SimpleFunction('show', 'show')
		);
	}

	/**
	 * Returns a list of filters to add to the existing list.
	 *
	 * @return array An array of filters
	 */
	public function getFilters()
	{
		$language = $this->container->get('system.language');

		return array(
			new \Twig_SimpleFilter('trans', array($language, 'translate')),
			new \Twig_SimpleFilter('lang', array($language, 'translate')),
			new \Twig_SimpleFilter('translate', array($language, 'translate')),
			new \Twig_SimpleFilter('_', array($language, 'translate')),
			new \Twig_SimpleFilter('sprintf', function () use ($language)
			{
				$args = func_get_args();

				return call_user_func_array(array($language, 'sprintf'), $args);
			}),
			new \Twig_SimpleFilter('plural', function () use ($language)
			{
				$args = func_get_args();

				return call_user_func_array(array($language, 'plural'), $args);
			})
		);
	}

	/**
	 * Method to get property Container
	 *
	 * @return  Container
	 */
	public function getContainer()
	{
		return $this->container;
	}

	/**
	 * Method to set property container
	 *
	 * @param   Container $container
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setContainer($container)
	{
		$this->container = $container;

		return $this;
	}
}
