<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Controller\Middleware;

use Windwalker\Core\Error\ErrorManager;
use Windwalker\Core\Response\Buffer\JsonBuffer;
use Windwalker\Core\Utilities\Debug\BacktraceHelper;
use Windwalker\Core\View\AbstractView;
use Windwalker\Debugger\Helper\DebuggerHelper;
use Windwalker\Utilities\Arr;

/**
 * The RenderViewMiddleware class.
 *
 * @since  3.0
 */
class JsonApiMiddleware extends AbstractControllerMiddleware
{
    /**
     * Call next middleware.
     *
     * @param   ControllerData $data
     *
     * @return  string
     */
    public function execute($data = null)
    {
        if (class_exists(DebuggerHelper::class)) {
            DebuggerHelper::disableConsole();
        }

        try {
            $result = $this->next->execute($data);

            if ($result instanceof AbstractView) {
                $result = $result->getHandledData();
            }

            $message = $this->getMessage();

            return new JsonBuffer($message, $result);
        } catch (\Exception $e) {
            return $this->handleException($e);
        } catch (\Throwable $t) {
            return $this->handleException(new \ErrorException(
                $t->getMessage(),
                $t->getCode(),
                E_ERROR,
                $t->getFile(),
                $t->getLine(),
                $t
            ));
        }
    }

    /**
     * handleException
     *
     * @param \Exception $e
     *
     * @return  JsonBuffer
     * @throws \InvalidArgumentException
     */
    protected function handleException(\Exception $e)
    {
        $data = [];

        if ($this->controller->app->get('system.debug')) {
            $data['exception'] = get_class($e);
            $data['backtrace'] = BacktraceHelper::normalizeBacktraces($e->getTrace());

            if (class_exists(DebuggerHelper::class)) {
                try {
                    $data['debug_messages'] = (array) DebuggerHelper::getInstance()->get('debug.messages');
                } catch (\Exception $e) {
                    // None
                }
            }
        }

        $this->controller->setResponse(
            $this->controller
                ->getResponse()
                ->withStatus(
                    ErrorManager::normalizeCode($e->getCode()),
                    ErrorManager::normalizeMessage($e->getMessage())
                )
        );

        $message = !WINDWALKER_DEBUG ? $e->getMessage() : sprintf(
            '#%d %s - File: %s (%d)',
            $e->getCode(),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );

        return new JsonBuffer($message, $data, false, $e->getCode());
    }

    /**
     * getMessage
     *
     * @return  string
     */
    protected function getMessage()
    {
        $msg = $this->controller->app->session->getFlashBag()->takeAll();
        $msg = implode("\n", Arr::flatten($msg));

        return $msg;
    }
}
