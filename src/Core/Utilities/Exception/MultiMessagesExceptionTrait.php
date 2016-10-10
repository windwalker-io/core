<?php
/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Utilities\Exception;

/**
 * The MultiMessagesExceptionTrait class.
 *
 * @since  3.0
 */
trait MultiMessagesExceptionTrait
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

		parent::__construct(implode(PHP_EOL, (array) $this->messages), $code, $previous);
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
