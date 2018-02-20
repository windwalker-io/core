<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Mailer\Adapter;

use Windwalker\Core\Mailer\MailMessage;
use Windwalker\Structure\Structure;

/**
 * The SwiftMailerAdapter class.
 *
 * @since  3.0
 */
class SwiftMailerAdapter implements MailerAdapterInterface
{
    /**
     * Property mailer.
     *
     * @var  \Swift_Mailer
     */
    protected $mailer;

    /**
     * Property message.
     *
     * @var  \Swift_Message
     */
    protected $message;

    /**
     * SwiftMailerAdapter constructor.
     *
     * @param \Swift_Mailer $mailer
     */
    public function __construct(\Swift_Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * createMessage
     *
     * @return  \Swift_Message
     */
    public function createMessage()
    {
        return \Swift_Message::newInstance();
    }

    /**
     * send
     *
     * @param MailMessage $message
     *
     * @return  int
     */
    public function send(MailMessage $message)
    {
        $msg = \Swift_Message::newInstance();

        $type = $message->getHtml() ? 'text/html' : null;

        $msg->setSubject($message->getSubject())
            ->setTo($message->getTo())
            ->setFrom($message->getFrom())
            ->setCc($message->getCc())
            ->setBcc($message->getBcc())
            ->setReplyTo($message->getReplyTo())
            ->setBody($message->getBody(), $type, 'utf8');

        $files = $message->getFiles();

        foreach ($files as $file) {
            $attach = \Swift_Attachment::newInstance();

            if ($file->getFilename()) {
                $attach->setFilename($file->getFilename());
            }

            if ($file->getContentType()) {
                $attach->setContentType($file->getContentType());
            }

            $attach->setBody($file->getBody());

            $msg->attach($attach);
        }

        return $this->getMailer()->send($msg);
    }

    /**
     * Method to get property Mailer
     *
     * @return  \Swift_Mailer
     */
    public function getMailer()
    {
        return $this->mailer;
    }

    /**
     * Method to set property mailer
     *
     * @param   \Swift_Mailer $mailer
     *
     * @return  static  Return self to support chaining.
     */
    public function setMailer($mailer)
    {
        $this->mailer = $mailer;

        return $this;
    }

    /**
     * createSwiftTransport
     *
     * @param  string $transport
     * @param  array  $config
     *
     * @return \Swift_Transport
     */
    public static function createTransport($transport, array $config)
    {
        $config = new Structure($config);

        switch ($transport) {
            case 'smtp':

                $instance = \Swift_SmtpTransport::newInstance(
                    $config->get('smtp.host'),
                    $config->get('smtp.port', 2525),
                    $config->get('smtp.security', 'tls')
                )->setUsername($config->get('smtp.username'))
                    ->setPassword($config->get('smtp.password'));

                if ($config->exists('smtp.local')) {
                    $instance->setLocalDomain($config->get('local'));
                }

                if (!$config->get('smtp.verify', true)) {
                    $instance->setStreamOptions(['ssl' => ['allow_self_signed' => true, 'verify_peer' => false]]);
                }

                break;

            case 'sendmail':

                $instance = \Swift_SendmailTransport::newInstance($config->get('sendmail'));

                break;

            default:
                $instance = \Swift_MailTransport::newInstance();
        }

        return $instance;
    }
}
