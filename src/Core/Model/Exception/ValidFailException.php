<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Core\Model\Exception;

/**
 * Class ValidFailException
 *
 * @since  2.0
 */
class ValidFailException extends \Exception
{
	/**
	 * Property messages.
	 *
	 * @var  array
	 */
	protected $messages;

	/**
	 * Class init.
	 *
	 * @param string|array $messages
	 * @param int          $code
	 * @param \Exception   $previous
	 */
	public function __construct($messages = null, $code = 0, \Exception $previous = null)
	{
		$this->messages = (array) $messages;

		foreach ($this->messages as &$msgs)
		{
			$msgs = implode(PHP_EOL, (array) $msgs);
		}

		parent::__construct(implode(PHP_EOL, (array) $messages), $code, $previous);
	}

	/**
	 * Method to get property Messages
	 *
	 * @return  array
	 */
	public function getMessages()
	{
		return $this->messages;
	}

	/**
	 * Method to set property messages
	 *
	 * @param   array $messages
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setMessages($messages)
	{
		$this->messages = $messages;

		return $this;
	}
}
