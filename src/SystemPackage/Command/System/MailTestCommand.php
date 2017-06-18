<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\SystemPackage\Command\System;

use Windwalker\Core\Console\CoreCommand;
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
	 * Execute this command.
	 *
	 * @return int
	 * @throws \InvalidArgumentException
	 *
	 * @since  2.0
	 */
	protected function doExecute()
	{
		$to = $this->getArgument(0, $this->console->get('mail.from.email'));

		if (!$to)
		{
			throw new \InvalidArgumentException('Please add email to your mail settings.');
		}

		$custom = $this->getOption('c', '');

		if ($custom)
		{
			$custom = '<p><strong>Custom message:</strong> ' . $custom . '</p>';
		}

		$body = sprintf($this->getBody(), $custom);

		Mailer::send(function (MailMessage $message) use ($to, $body)
		{
			$message->subject('Test Message from Windwalker')
				->to($to)
				->body($body);
		});

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
