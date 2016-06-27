<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\SystemPackage\Command\System;

/**
 * The UpCommand class.
 *
 * @since  {DEPLOY_VERSION}
 */
class UpCommand extends DownCommand
{
	/**
	 * Console(Argument) name.
	 *
	 * @var  string
	 */
	protected $name = 'up';

	/**
	 * The command description.
	 *
	 * @var  string
	 */
	protected $description = 'Make site online.';

	/**
	 * The usage to tell user how to use this command.
	 *
	 * @var string
	 */
	protected $usage = '%s [options]';

	/**
	 * Property offline.
	 *
	 * @var  boolean
	 */
	protected $offline = false;
}
