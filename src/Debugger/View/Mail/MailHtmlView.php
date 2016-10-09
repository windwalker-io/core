<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Debugger\View\Mail;

use Windwalker\Data\Data;
use Windwalker\Debugger\View\AbstractDebuggerHtmlView;

/**
 * The MailHtmlView class.
 *
 * @since  {DEPLOY_VERSION}
 */
class MailHtmlView extends AbstractDebuggerHtmlView
{
	/**
	 * prepareData
	 *
	 * @param \Windwalker\Data\Data $data
	 *
	 * @return  void
	 */
	protected function prepareData($data)
	{
		parent::prepareData($data);

		$data->item = new Data;
	}
}
