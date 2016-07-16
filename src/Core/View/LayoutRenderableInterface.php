<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\View;

use Windwalker\Renderer\AbstractRenderer;

/**
 * Interface LayoutRenderableInterface
 *
 * @since  {DEPLOY_VERSION}
 */
interface LayoutRenderableInterface
{
	/**
	 * Method to get property Layout
	 *
	 * @return  string
	 */
	public function getLayout();

	/**
	 * Method to set property layout
	 *
	 * @param   string $layout
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setLayout($layout);

	/**
	 * Method to get property Renderer
	 *
	 * @return  AbstractRenderer
	 */
	public function getRenderer();

	/**
	 * Method to set property renderer
	 *
	 * @param   AbstractRenderer|string $renderer
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setRenderer($renderer);

	/**
	 * registerPaths
	 *
	 * @param bool $refresh
	 *
	 * @return static
	 */
	public function registerPaths($refresh = false);

	/**
	 * dumpPaths
	 *
	 * @param bool $multilingual
	 *
	 * @return  array
	 */
	public function dumpPaths($multilingual = false);

	/**
	 * addPath
	 *
	 * @param string $path
	 * @param int    $priority
	 *
	 * @return  static
	 */
	public function addPath($path, $priority = 100);
}
