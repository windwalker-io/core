<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Error\Handler;

use Windwalker\Core\Logger\LoggerManager;
use Windwalker\Core\Utilities\Debug\BacktraceHelper;

/**
 * The ErrorLogHandler class.
 *
 * @since  3.0
 */
class ErrorLogHandler implements ErrorHandlerInterface
{
    /**
     * Property manager.
     *
     * @var  LoggerManager
     */
    protected $manager;

    /**
     * ErrorLogHandler constructor.
     *
     * @param LoggerManager $manager
     */
    public function __construct(LoggerManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * __invoke
     *
     * @param  \Exception|\Throwable $e
     *
     * @return  void
     * @throws \Exception
     */
    public function __invoke($e)
    {
        // Do not log 4xx errors
        $code = $e->getCode();

        if ($code < 400 || $code >= 500) {
            $message = sprintf(
                'Code: %s - %s - File: %s (%d)',
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );

            $traces = '';

            foreach (BacktraceHelper::normalizeBacktraces($e->getTrace()) as $i => $trace) {
                $traces .= '    #' . ($i + 1) . ' - ' . $trace['function'] . ' ' . $trace['file'] . "\n";
            }

            $this->manager->error('error', $message . "\n" . $traces, ['exception' => $e]);
        }
    }
}
