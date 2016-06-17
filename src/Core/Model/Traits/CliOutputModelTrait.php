<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Model\Traits;

use Windwalker\Console\IO\IOInterface;

/**
 * The CliOutputModelTrait class.
 *
 * @since  {DEPLOY_VERSION}
 */
trait CliOutputModelTrait
{
	/**
	 * Property io.
	 *
	 * @var  IOInterface
	 */
	protected $io;

	/**
	 * out
	 *
	 * @param string $text
	 * @param bool   $nl
	 *
	 * @return  static
	 */
	public function out($text, $nl = true)
	{
		$this->io->out($text, $nl);

		return $this;
	}

	/**
	 * in
	 *
	 * @return  string
	 */
	public function in()
	{
		return $this->io->in();
	}

	/**
	 * Method to get property Io
	 *
	 * @return  IOInterface
	 */
	public function getIo()
	{
		return $this->io;
	}

	/**
	 * Method to set property io
	 *
	 * @param   IOInterface $io
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setIo($io)
	{
		$this->io = $io;

		return $this;
	}
}
