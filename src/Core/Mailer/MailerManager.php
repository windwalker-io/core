<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Mailer;

use Windwalker\Core\Mailer\Adapter\MailerAdapterInterface;

/**
 * The Mailer class.
 *
 * @since  {DEPLOY_VERSION}
 */
class MailerManager
{
	/**
	 * Property adapter.
	 *
	 * @var  MailerAdapterInterface
	 */
	protected $adapter;

	/**
	 * Property messageClass.
	 *
	 * @var  string
	 */
	protected $messageClass = MailMessage::class;

	/**
	 * Mailer constructor.
	 *
	 * @param MailerAdapterInterface $adapter
	 */
	public function __construct(MailerAdapterInterface $adapter = null)
	{
		$this->adapter = $adapter;
	}

	/**
	 * createMessage
	 *
	 * @param string $subject
	 * @param string $content
	 * @param bool   $html
	 *
	 * @return  MailMessage
	 */
	public function createMessage($subject = null, $content = null, $html = true)
	{
		$class = $this->messageClass;

		return new $class($subject, $content, $html);
	}

	/**
	 * send
	 *
	 * @param MailMessage $message
	 *
	 * @return  boolean
	 */
	public function send(MailMessage $message)
	{
		return $this->getAdapter()->send($message);
	}

	/**
	 * Method to set property messageClass
	 *
	 * @param   mixed $messageClass
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setMessageClass($messageClass)
	{
		$this->messageClass = $messageClass;

		return $this;
	}

	/**
	 * Method to get property Adapter
	 *
	 * @return  MailerAdapterInterface
	 */
	public function getAdapter()
	{
		return $this->adapter;
	}

	/**
	 * Method to set property adapter
	 *
	 * @param   MailerAdapterInterface $adapter
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setAdapter(MailerAdapterInterface $adapter)
	{
		$this->adapter = $adapter;

		return $this;
	}
}
