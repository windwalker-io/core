<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Widget;

use Windwalker\Core\Ioc;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Renderer\RendererManager;
use Windwalker\Renderer\RendererInterface;

/**
 * The WidgetHelper class.
 * 
 * @since  2.1.1
 */
class WidgetManager
{
	const ENGINE_PHP      = 'php';
	const ENGINE_BLADE    = 'blade';
	const ENGINE_EDGE     = 'edge';
	const ENGINE_TWIG     = 'twig';
	const ENGINE_MUSTACHE = 'mustache';

	/**
	 * Property widgetClass.
	 *
	 * @var  string
	 */
	protected $widgetClass = Widget::class;

	/**
	 * Property rendererManager.
	 *
	 * @var  RendererManager
	 */
	protected $rendererManager;

	/**
	 * WidgetManager constructor.
	 *
	 * @param RendererManager $rendererManager
	 */
	public function __construct(RendererManager $rendererManager)
	{
		$this->rendererManager = $rendererManager;
	}

	/**
	 * render
	 *
	 * @param string                 $layout
	 * @param array                  $data
	 * @param string                 $engine
	 * @param string|AbstractPackage $package
	 *
	 * @return string
	 */
	public function render($layout, $data = [], $engine = self::ENGINE_PHP, $package = null)
	{
		return $this->createWidget($layout, $engine, $package)->render($data);
	}

	/**
	 * create
	 *
	 * @param string                   $layout
	 * @param string|RendererInterface $engine
	 * @param string|AbstractPackage   $package
	 *
	 * @return  Widget
	 */
	public function createWidget($layout, $engine = null, $package = null)
	{
		$class = $this->widgetClass;

		$engine = $this->rendererManager->getRenderer($engine ? : static::ENGINE_PHP);

		// Prepare package
		$package = $package ? : Ioc::get('current.package');

		/** @var Widget $widget */
		$widget = new $class($layout, $engine, $package);

		$widget->setData($this->rendererManager->getGlobals());

		return $widget;
	}

	/**
	 * Method to get property RendererManager
	 *
	 * @return  RendererManager
	 */
	public function getRendererManager()
	{
		return $this->rendererManager;
	}

	/**
	 * Method to set property rendererManager
	 *
	 * @param   RendererManager $rendererManager
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setRendererManager($rendererManager)
	{
		$this->rendererManager = $rendererManager;

		return $this;
	}
}
