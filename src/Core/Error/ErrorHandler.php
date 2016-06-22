<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Core\Error;

use Windwalker\Application\Web\Response;
use Windwalker\Core\Facade\AbstractProxyFacade;
use Windwalker\Core\Renderer\RendererHelper;
use Windwalker\Http\Response\HtmlResponse;
use Windwalker\Profiler\Point\Collector;
use Windwalker\Renderer\PhpRenderer;

/**
 * Class ErrorHandler
 *
 * @see  ErrorManager
 *
 * @method  static  void  error($code, $message, $file, $line, $context)
 * @method  static  void  exception($exception)
 * @method  static  void  respond($exception)
 * @method  static  void  getErrorTemplate()
 * @method  static  void  setErrorTemplate($errorTemplate)
 * @method  static  string  getLevelName($constant)
 * @method  static  int     getLevelCode($name)
 * @method  static  void    register($restore = true, $type = null, $shotdown = false)
 * @method  static  void    restore()
 * @method  static  callable[]    getHandlers()
 * @method  static  ErrorManager  setHandlers()
 * @method  static  ErrorManager  addHandler(callable $handler)
 * @method  static  ErrorManager  removeHandler($name)
 *
 * @since  3.0
 */
class ErrorHandler extends AbstractProxyFacade
{
	/**
	 * Property _key.
	 *
	 * @var  string
	 */
	protected static $_key = 'error.handler';
}
