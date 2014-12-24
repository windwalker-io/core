<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Test\View\Stub;

use Windwalker\Core\View\TwigHtmlView;

/**
 * The StubTwigHtmlView class.
 * 
 * @since  {DEPLOY_VERSION}
 */
class StubTwigHtmlView extends TwigHtmlView
{
	/**
	 * initialise
	 *
	 * @return  void
	 */
	protected function initialise()
	{
		$this->renderer->addExtension(new StubExtension);
	}
}
