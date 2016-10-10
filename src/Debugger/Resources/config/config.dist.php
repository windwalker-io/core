<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

return [
	'providers' =>[
		300 => \Windwalker\Debugger\Provider\ProfilerProvider::class
	],

	'routing' => [
		'files' => [
			'debugger' => WINDWALKER_DEBUGGER_ROOT . '/routing.yml'
		]
	],

	'middlewares' => [

	],

	'configs' => [

	],

	'listeners' => [
		300 => \Windwalker\Debugger\Listener\DebuggerListener::class
	],

	'console' => [
		'commands' => [
		
		]
	]
];
