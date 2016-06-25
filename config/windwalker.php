<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

return [
	'packages' => [

	],

	'providers' =>[
		'logger' => \Windwalker\Core\Provider\LoggerProvider::class,
		'event'  => \Windwalker\Core\Provider\EventProvider::class,
		'mailer' => \Windwalker\Core\Mailer\MailerProvider::class,
		'swiftmailer' => \Windwalker\Core\Mailer\SwiftMailerProvider::class
	],

	'configs' => [

	],

	'listeners' => [
		
	],
	
	'path' => [
		'root'       => null,
		'bin'        => null,
		'cache'      => null,
		'etc'        => null,
		'logs'       => null,
		'resources'  => null,
		'source'     => null,
		'temp'       => null,
		'templates'  => null,
		'vendor'     => null,
		'public'     => null,
		'migrations' => null,
		'seeders'    => null,
		'languages'  => null,
	]
];
