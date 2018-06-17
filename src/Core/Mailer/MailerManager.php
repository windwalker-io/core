<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Mailer;

use Windwalker\Core\Event\EventDispatcher;
use Windwalker\Core\Ioc;
use Windwalker\Core\Mailer\Adapter\MailerAdapterInterface;
use Windwalker\Event\DispatcherAwareTrait;

/**
 * The Mailer class.
 *
 * @since  3.0
 */
class MailerManager
{
    use DispatcherAwareTrait;

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
     * @param EventDispatcher        $dispatcher
     */
    public function __construct(MailerAdapterInterface $adapter = null, EventDispatcher $dispatcher = null)
    {
        $this->adapter    = $adapter;
        $this->dispatcher = $dispatcher ?: new EventDispatcher();
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

        $message = new $class($subject, $content, $html);

        $this->triggerEvent('onMailerAfterCreateMessage', [
            'message' => $message,
            'manager' => $this,
        ]);

        return $message;
    }

    /**
     * send
     *
     * @param MailMessage|callable $message
     * @param array                $options
     *
     * @return  boolean
     *
     */
    public function send($message, array $options = [])
    {
        $config = Ioc::getConfig();

        if (empty($options['force']) && !$config->get('mail.enabled', true)) {
            return true;
        }

        if (\is_callable($message)) {
            $msgObject = $this->createMessage();

            $message = $message($msgObject, $this);

            if (!$message instanceof MailMessage) {
                $message = $msgObject;
            }
        }

        if (!$message instanceof MailMessage) {
            throw new \InvalidArgumentException(sprintf('Mail Message should instance of %s', MailMessage::class));
        }

        $this->triggerEvent('onMailerBeforeSend', [
            'message' => $message,
            'manager' => $this,
        ]);

        // Set default sender
        if (!$message->getFrom()) {
            if ($config->exists('mail.from.email')) {
                $message->from($config->get('mail.from.email'), $config->get('mail.from.name'));
            }
        }

        $result = $this->getAdapter()->send($message);

        $this->triggerEvent('onMailerAfterSend', [
            'message' => $message,
            'manager' => $this,
            'result' => &$result,
        ]);

        return $result;
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
