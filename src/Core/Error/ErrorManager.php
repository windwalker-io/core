<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Error;

use Windwalker\Core\Application\WebApplication;
use Windwalker\Http\Helper\ResponseHelper;
use Windwalker\Http\Response\HtmlResponse;
use Windwalker\String\Mbstring;
use Windwalker\String\Str;

/**
 * The ErrorManager class.
 *
 * @since  3.0
 */
class ErrorManager
{
    /**
     * Property app.
     *
     * @var  WebApplication
     */
    protected $app;

    /**
     * Property errorTemplate.
     *
     * @var  string
     */
    protected $errorTemplate = 'windwalker.error.default';

    /**
     * Property handler.
     *
     * @var  callable[]
     */
    protected $handlers;

    /**
     * Property engine.
     *
     * @var  string
     */
    protected $engine = 'php';

    /**
     * Property constants.
     *
     * @var  array
     */
    protected $constants = [
        E_ERROR => 'E_ERROR',
        E_WARNING => 'E_WARNING',
        E_PARSE => 'E_PARSE',
        E_NOTICE => 'E_NOTICE',
        E_CORE_ERROR => 'E_CORE_ERROR',
        E_CORE_WARNING => 'E_CORE_WARNING',
        E_COMPILE_ERROR => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING => 'E_COMPILE_WARNING',
        E_USER_ERROR => 'E_USER_ERROR',
        E_USER_WARNING => 'E_USER_WARNING',
        E_STRICT => 'E_STRICT',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_DEPRECATED => 'E_DEPRECATED',
        E_USER_DEPRECATED => 'E_USER_DEPRECATED',
        E_ALL => 'E_ALL',
    ];

    /**
     * ErrorManager constructor.
     *
     * @param WebApplication $app
     */
    public function __construct(WebApplication $app)
    {
        $this->app = $app;

        $this->addHandler([$this, 'respond'], 'default');
    }

    /**
     * down
     *
     * @return  void
     * @throws \ErrorException
     */
    public function down()
    {
        $error = error_get_last();

        if ($error && $error['type'] === E_ERROR) {
            $this->error(...array_values($error));
        }
    }

    /**
     * The error handler.
     *
     * @param   integer $code    The level of the error raised, as an integer.
     * @param   string  $message The error message, as a string.
     * @param   string  $file    The filename that the error was raised in, as a string.
     * @param   integer $line    The line number the error was raised at, as an integer.
     * @param   mixed   $context An array that contains variables in the scope which this error occurred.
     *
     * @throws  \ErrorException
     * @return  void
     *
     * @see  http://php.net/manual/en/function.set-error-handler.php
     */
    public function error($code, $message, $file, $line, $context = null)
    {
        if (!(error_reporting() & $code)) {
            return;
        }

        $content = sprintf('%s. File: %s (line: %s)', $message, $file, $line);

        throw new \ErrorException($content, 500, $code, $file, $line, new \Error());
    }

    /**
     * The exception handler.
     *
     * @param \Throwable|\Exception $exception The exception object.
     *
     * @return  void
     *
     * @link  http://php.net/manual/en/function.set-exception-handler.php
     */
    public function exception($exception)
    {
        try {
            foreach ($this->handlers as $handler) {
                $handler($exception);
            }
        } catch (\Throwable $e) {
            $msg = "Infinity loop in exception & error handler. \nMessage:\n" . $e;

            if ($this->app->get('system.debug')) {
                exit($msg);
            }

            exit($e->getMessage());
        }

        exit();
    }

    /**
     * respond
     *
     * @param \Throwable $exception
     *
     * @return  void
     * @throws \InvalidArgumentException
     */
    protected function respond($exception)
    {
        $renderer = $this->app->renderer->getRenderer($this->engine);

        $body = $renderer->render(
            $this->app->get('error.template', 'windwalker.error.default'),
            ['exception' => $exception]
        );

        $code = $exception->getCode();

        if ($code < 400 || $code >= 500) {
            $code = 500;
        }

        $response = (new HtmlResponse($body))->withStatus($code);

        $this->app->server->getOutput()->respond($response);
    }

    /**
     * Method to get property ErrorTemplate
     *
     * @return  string
     */
    public function getErrorTemplate()
    {
        return $this->errorTemplate;
    }

    /**
     * Method to set property errorTemplate
     *
     * @param   string $errorTemplate
     * @param   string $engine
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    public function setErrorTemplate($errorTemplate, $engine = null)
    {
        if (!\is_string($errorTemplate)) {
            throw new \InvalidArgumentException('Please use string as template name (Example: "folder.file").');
        }

        $this->errorTemplate = $errorTemplate;

        if ($engine) {
            $this->setEngine($engine);
        }
    }

    /**
     * getLevelName
     *
     * @param integer $constant
     *
     * @return  string
     */
    public function getLevelName($constant)
    {
        if ($this->constants[$constant]) {
            return $this->constants[$constant];
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
    public function getLevelCode($name)
    {
        $name = strtoupper(trim($name));

        return array_search($name, $this->constants);
    }

    /**
     * registerErrorHandler
     *
     * @param bool $restore
     * @param int  $type
     * @param bool $shutdown
     */
    public function register($restore = true, $type = E_ALL | E_STRICT, $shutdown = false)
    {
        if ($type === null) {
            $type = E_ALL | E_STRICT;
        }

        if ($restore) {
            $this->restore();
        }

        set_error_handler([$this, 'error'], $type);
        set_exception_handler([$this, 'exception']);

        if ($shutdown) {
            register_shutdown_function([$this, 'down']);
        }
    }

    /**
     * restore
     *
     * @return  void
     */
    public function restore()
    {
        restore_error_handler();
        restore_exception_handler();
    }

    /**
     * Method to set property handler
     *
     * @param   callable $handler
     * @param   string   $name
     *
     * @return static Return self to support chaining.
     */
    public function addHandler(callable $handler, $name = null)
    {
        if ($name) {
            $this->handlers[$name] = $handler;
        } else {
            $this->handlers[] = $handler;
        }

        return $this;
    }

    /**
     * removeHandler
     *
     * @param   string $name
     *
     * @return  static
     */
    public function removeHandler($name)
    {
        unset($this->handlers[$name]);

        return $this;
    }

    /**
     * Method to get property Handlers
     *
     * @return  \callable[]
     */
    public function getHandlers()
    {
        return $this->handlers;
    }

    /**
     * Method to set property handlers
     *
     * @param   \callable[] $handlers
     *
     * @return  static  Return self to support chaining.
     */
    public function setHandlers($handlers)
    {
        $this->handlers = $handlers;

        return $this;
    }

    /**
     * Method to get property Engine
     *
     * @return  string
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * Method to set property engine
     *
     * @param   string $engine
     *
     * @return  static  Return self to support chaining.
     */
    public function setEngine($engine)
    {
        $this->engine = $engine;

        return $this;
    }

    /**
     * normalizeCode
     *
     * @param int $code
     *
     * @return  int
     */
    public static function normalizeCode($code)
    {
        $stringCode = (string) $code;

        if (strlen($stringCode) > 3) {
            $code = substr($stringCode, 0, 3);
        }

        return ResponseHelper::validateStatus($code) ? $code : 500;
    }

    /**
     * normalizeMessage
     *
     * @param string $message
     *
     * @return  string
     */
    public static function normalizeMessage($message)
    {
        if (Mbstring::isUtf8($message)) {
            $message = str_replace('%20', ' ', rawurlencode($message));
        }

        return trim(explode("\n", $message)[0]);
    }
}
