<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\SystemPackage\Command\System;

use Windwalker\Core\Console\CoreCommand;
use Windwalker\Core\DateTime\Chronos;
use Windwalker\Core\Mailer\Mailer;
use Windwalker\Core\Mailer\MailMessage;

/**
 * The ModeCommand class.
 *
 * @since  3.2.2
 */
class MailTestCommand extends CoreCommand
{
    /**
     * Console(Argument) name.
     *
     * @var  string
     */
    protected $name = 'mail-test';

    /**
     * The command description.
     *
     * @var  string
     */
    protected $description = 'This command will send a test mail by your mail settings.';

    /**
     * init
     *
     * @return  void
     *
     * @since  3.4
     */
    protected function init()
    {
        $this->addOption('m')
            ->alias('message')
            ->description('Message to add to mail body.');

        $this->addOption('s')
            ->alias('subject')
            ->description('Mail subject title.');
    }

    /**
     * Execute this command.
     *
     * @return int
     * @throws \InvalidArgumentException
     *
     * @since  2.0
     */
    protected function doExecute()
    {
        $custom = $this->getOption('message', '');
        $subject = $this->getOption('subject');

        $to = $this->getArgument(0, $this->console->get('mail.from.email'));

        if (!$to) {
            throw new \InvalidArgumentException('Please add email to your mail settings.');
        }

        if ($custom) {
            $custom = '<p><strong>Custom message:</strong> ' . $custom . '</p>';
        }

        $body = sprintf($this->getBody(), $custom);

        try {
            $this->out('Sending...');

            Mailer::send(function (MailMessage $message) use ($to, $body, $subject) {
                $title = 'Test Message from Windwalker' . ($subject ? ': ' . $subject : '')
                    . ' - ' . Chronos::toLocalTime('now', 'Y-m-d H:i:s');

                $message->subject($title)
                    ->to($to)
                    ->replyTo($to)
                    ->body($body);
            }, ['force' => true]);
        } catch (\Exception $e) {
            $this->out('<error>[ERROR] Send mail failure.</error>')
                ->out()
                ->out('<option>Error message:</option>')
                ->out($e->getMessage());

            $this->console->close();
        }

        $this->out(sprintf('Test mail sent to: <info>%s</info>.', $to));

        return true;
    }

    /**
     * getBody
     *
     * @return  string
     */
    protected function getBody()
    {
        return <<<HTML
<p>Hello</p>

<p>This is a test mail from Windwalker. If you receive this mail, it means you have your mail settings correct.</p>

%s

<p>Have a good day.</p>
HTML;
    }
}
