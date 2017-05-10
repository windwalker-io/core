<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Test\Error;

use Windwalker\Application\Web\Response;
use Windwalker\Core\Error\ErrorHandler;
use Windwalker\Core\Renderer\RendererHelper;
use Windwalker\Renderer\PhpRenderer;

/**
 * The StubErrorHandler class.
 * 
 * @since  2.1.1
 */
abstract class StubErrorHandler extends ErrorHandler
{
	/**
	 * Property response.
	 *
	 * @var Response
	 */
	public static $response;

	/**
	 * respond
	 *
	 * @param \Exception $exception
	 *
	 * @return  void
	 */
	protected static function respond($exception)
	{
		$renderer = new PhpRenderer(RendererHelper::getGlobalPaths());

		$body = $renderer->render(static::$errorTemplate, ['exception' => $exception]);

		$response = new Response;

		$response->setHeader('Status', $exception->getCode() ? : 500)
			->setBody($body);

		static::$response = $response;
	}
}
