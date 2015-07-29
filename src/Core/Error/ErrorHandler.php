<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT Taiwan, Inc. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Core\Error;

use Windwalker\Application\Web\Response;
use Windwalker\Core\Renderer\RendererHelper;
use Windwalker\Renderer\PhpRenderer;

/**
 * Class SimpleErrorHandler
 *
 * @since 1.0
 */
class ErrorHandler
{
	/**
	 * Property constants.
	 *
	 * @var  array
	 */
	protected static $constants = array(
		E_ERROR           => 'E_ERROR',
		E_WARNING         => 'E_WARNING',
		E_PARSE           => 'E_PARSE',
		E_NOTICE          => 'E_NOTICE',
		E_CORE_ERROR      => 'E_CORE_ERROR',
		E_CORE_WARNING    => 'E_CORE_WARNING',
		E_COMPILE_ERROR   => 'E_COMPILE_ERROR',
		E_COMPILE_WARNING => 'E_COMPILE_WARNING',
		E_USER_ERROR      => 'E_USER_ERROR',
		E_USER_WARNING    => 'E_USER_WARNING',
		E_STRICT          => 'E_STRICT',
		E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
		E_DEPRECATED      => 'E_DEPRECATED',
		E_USER_DEPRECATED => 'E_USER_DEPRECATED',
		E_ALL             => 'E_ALL'
	);

	/**
	 * Property errorTemplate.
	 *
	 * @var  string
	 */
	protected static $errorTemplate = 'windwalker.error.default';

	/**
	 * The error handler.
	 *
	 * @param   integer  $code     The level of the error raised, as an integer.
	 * @param   string   $message  The error message, as a string.
	 * @param   string   $file     The filename that the error was raised in, as a string.
	 * @param   integer  $line     The line number the error was raised at, as an integer.
	 * @param   mixed    $context  An array that contains variables in the scope which this error occurred.
	 *
	 * @throws  \ErrorException
	 * @return  void
	 *
	 * @see  http://php.net/manual/en/function.set-error-handler.php
	 */
	public static function error($code ,$message ,$file, $line, $context)
	{
		$content = sprintf('%s. File: %s (line: %s)', $message, $file, $line);

		$exception = new \ErrorException($content, $code, 1, $file, $line);

		static::respond($exception);
	}

	/**
	 * The exception handler.
	 *
	 * @param \Exception $exception The exception object.
	 *
	 * @return  void
	 *
	 * @see  http://php.net/manual/en/function.set-exception-handler.php
	 */
	public static function exception(\Exception $exception)
	{
		try
		{
			static::respond($exception);
		}
		catch (\Exception $e)
		{
			$msg = "Infinity loop in exception handler. \n\nException:\n" . $e;

			exit($msg);
		}
	}

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
			->setBody($body)
			->respond();

		exit();
	}

	/**
	 * Method to get property ErrorTemplate
	 *
	 * @return  string
	 */
	public static function getErrorTemplate()
	{
		return static::$errorTemplate;
	}

	/**
	 * Method to set property errorTemplate
	 *
	 * @param   string $errorTemplate
	 *
	 * @return  void
	 */
	public static function setErrorTemplate($errorTemplate)
	{
		if (!is_string($errorTemplate))
		{
			throw new \InvalidArgumentException('Please use string as template name (Example: "folder.file").');
		}

		static::$errorTemplate = $errorTemplate;
	}

	/**
	 * getLevelName
	 *
	 * @param integer $constant
	 *
	 * @return  string
	 */
	public static function getLevelName($constant)
	{
		if (static::$constants[$constant])
		{
			return static::$constants[$constant];
		}

		return null;
	}

	/**
	 * getLevelCode
	 *
	 * @param   string $name
	 *
	 * @return  integer|false
	 */
	public static function getLevelCode($name)
	{
		$name = strtoupper(trim($name));

		return array_search($name, static::$constants);
	}

	/**
	 * registerErrorHandler
	 *
	 * @param bool $restore
	 *
	 * @return void
	 */
	public static function register($restore = true)
	{
		if ($restore)
		{
			static::restore();
		}

		set_error_handler(array(get_called_class(), 'error'));
		set_exception_handler(array(get_called_class(), 'exception'));
	}

	/**
	 * restore
	 *
	 * @return  void
	 */
	public static function restore()
	{
		restore_error_handler();
		restore_exception_handler();
	}
}
 