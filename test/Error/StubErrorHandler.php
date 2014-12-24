<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Test\Error;

use Windwalker\Application\Web\Response;
use Windwalker\Core\Error\ErrorHandler;
use Windwalker\Core\Renderer\RendererHelper;
use Windwalker\Renderer\PhpRenderer;

/**
 * The StubErrorHandler class.
 * 
 * @since  {DEPLOY_VERSION}
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

		$body = $renderer->render(static::$errorTemplate, array('exception' => $exception));

		$response = new Response;

		$response->setHeader('Status', $exception->getCode() ? : 500)
			->setBody($body);

		static::$response = $response;
	}
}
