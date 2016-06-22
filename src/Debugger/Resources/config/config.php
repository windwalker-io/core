<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

return [
	'providers' =>[
		300 => \Windwalker\Debugger\Provider\ProfilerProvider::class
	],

	'routing' => [
		'files' => [
			'debugger' => __DIR__ . '/routing.yml'
		]
	],

	'middlewares' => [

	],

	'configs' => [

	],

	'listeners' => [
		300 => \Windwalker\Debugger\Listener\DebuggerListener::class
	]
];
