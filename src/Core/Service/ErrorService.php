<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 .
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Service;

use Windwalker\Core\Runtime\Config;
use Windwalker\Http\Helper\ResponseHelper;
use Windwalker\Utilities\Utf8String;

/**
 * The ErrorService class.
 *
 * @since  __DEPLOY_VERSION__
 */
class ErrorService
{
    /**
     * Property errorTemplate.
     *
     * @var  string
     */
    protected string $errorTemplate = 'windwalker.error.default';

    /**
     * Property handler.
     *
     * @var  callable[]
     */
    protected array $handlers = [];

    /**
     * Property engine.
     *
     * @var  string
     */
    protected string $engine = 'php';

    /**
     * Property constants.
     *
     * @var  array
     */
    protected array $constants = [
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
     * @var Config
     */
    protected Config $config;

    /**
     * ErrorService constructor.
     *
     * @param  Config  $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config->proxy('error');
    }

    /**
     * down
     *
     * @return  void
     * @throws \ErrorException
     */
    public function down(): void
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
    public function error(int $code, string $message, string $file, int $line, $context = null): void
    {
        if (error_reporting() === 0) {
            return;
        }

        $content = sprintf('%s. File: %s (line: %s)', $message, $file, $line);

        throw new \ErrorException($content, 500, $code, $file, $line, new \Error());
    }

    /**
     * The exception handler.
     *
     * @param \Throwable $exception The exception object.
     *
     * @return  void
     *
     * @link  http://php.net/manual/en/function.set-exception-handler.php
     */
    public function exception(\Throwable $exception): void
    {
        try {
            foreach ($this->handlers as $handler) {
                $handler($exception);
            }
        } catch (\Throwable $e) {
            $msg = "Infinity loop in exception & error handler. \nMessage:\n" . $e;

            if ($this->config->get('system.debug')) {
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
    protected function simpleHandler(\Throwable $exception): void
    {
        if ($this->config->getDeep('app.debug')) {
            echo $exception;
        } else {
            echo $exception->getMessage();
        }

        // $renderer = $this->app->renderer->getRenderer($this->engine);
        //
        // $body = $renderer->render(
        //     $this->app->get('error.template', 'windwalker.error.default'),
        //     ['exception' => $exception]
        // );
        //
        // $code = $exception->getCode();
        //
        // if ($code < 400 || $code >= 500) {
        //     $code = 500;
        // }
        //
        // $response = (new HtmlResponse($body))->withStatus($code);
        //
        // $this->app->server->getOutput()->respond($response);
    }

    /**
     * Method to get property ErrorTemplate
     *
     * @return  string
     */
    public function getErrorTemplate(): string
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
    public function setErrorTemplate(string $errorTemplate, ?string $engine = null): void
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
    public function getLevelName(int $constant): ?string
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
    public function getLevelCode(string $name): int|bool
    {
        $name = strtoupper(trim($name));

        return array_search($name, $this->constants, true);
    }

    /**
     * registerErrorHandler
     *
     * @param bool $restore
     * @param int  $type
     * @param bool $shutdown
     */
    public function register(bool $restore = true, int $type = E_ALL | E_STRICT, bool $shutdown = false): void
    {
        $this->registerErrors($restore, $type);
        $this->registerExceptions($restore);

        if ($shutdown) {
            $this->registerShutdown();
        }
    }

    public function registerErrors(bool $restore = true, int $type = E_ALL | E_STRICT): void
    {
        if ($restore) {
            restore_error_handler();
        }

        set_error_handler([$this, 'error'], $type);
    }

    public function registerExceptions(bool $restore = true): void
    {
        if ($restore) {
            restore_exception_handler();
        }

        set_exception_handler([$this, 'exception']);
    }

    public function registerShutdown(): void
    {
        register_shutdown_function([$this, 'down']);
    }

    /**
     * restore
     *
     * @return  void
     */
    public function restore(): void
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
    public function addHandler(callable $handler, ?string $name = null)
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
    public function removeHandler(string $name)
    {
        unset($this->handlers[$name]);

        return $this;
    }

    /**
     * Method to get property Handlers
     *
     * @return  callable[]
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }

    /**
     * Method to set property handlers
     *
     * @param   callable[] $handlers
     *
     * @return  static  Return self to support chaining.
     */
    public function setHandlers(array $handlers)
    {
        $this->handlers = $handlers;

        return $this;
    }

    /**
     * Method to get property Engine
     *
     * @return  string
     */
    public function getEngine(): string
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
    public function setEngine(string $engine)
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
    public static function normalizeCode(mixed $code): int
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
    public static function normalizeMessage(string $message): string
    {
        if (Utf8String::isUtf8($message)) {
            $message = str_replace('%20', ' ', rawurlencode($message));
        }

        return trim(explode("\n", $message)[0]);
    }
}
