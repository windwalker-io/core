<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

$autoload = __DIR__ . '/../../vendor/autoload.php';

if (!is_file($autoload))
{
	$autoload = __DIR__ . '/../../../../autoload.php';
}

if (!is_file($autoload))
{
	exit('Please run <code>$ composer install</code> First.');
}

include_once $autoload;

include_once __DIR__ . '/define.php';

$host = defined('WINDWALKER_TEST_HOST') ? WINDWALKER_TEST_HOST : 'windwalker.io';
$uri = defined('WINDWALKER_TEST_URI') ? WINDWALKER_TEST_URI : '/flower/sakura';

$_SERVER['HTTP_HOST'] = $host;
$_SERVER['REQUEST_URI'] = $uri;
$_SERVER['SCRIPT_NAME'] = '/';
$_SERVER['PHP_SELF'] = $uri;

new \Windwalker\Core\Test\TestApplication;

define('WINDWALKER_DEBUG', true);
