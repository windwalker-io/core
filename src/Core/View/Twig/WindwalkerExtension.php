<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT Taiwan, Inc. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Core\View\Twig;

use Windwalker\Core\View\Helper\ViewHelper;
use Windwalker\Core\View\HtmlView;
use Windwalker\Data\Data;

/**
 * Class FormosaExtension
 *
 * @since 1.0
 */
class WindwalkerExtension extends \Twig_Extension
{
	/**
	 * Property view.
	 *
	 * @var  HtmlView
	 */
	protected $view;

	/**
	 * Class init
	 *
	 * @param HtmlView $view
	 */
	public function __construct(HtmlView $view = null)
	{
		$this->view = $view ? : new HtmlView;
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
	 * Method to get property View
	 *
	 * @return  HtmlView
	 */
	public function getView()
	{
		return $this->view;
	}

	/**
	 * Method to set property view
	 *
	 * @param   HtmlView $view
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setView($view)
	{
		$this->view = $view;

		return $this;
	}
}
 