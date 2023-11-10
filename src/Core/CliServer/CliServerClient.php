<?php

declare(strict_types=1);

namespace Windwalker\Core\CliServer;

use Psr\Http\Message\RequestInterface;
use Symfony\Component\Console\Terminal;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Reactor\WebSocket\WebSocketRequestInterface;
use Windwalker\Utilities\Str;

/**
 * The CliServerClient class.
 */
class CliServerClient
{
    protected int $terminalWidth;

    public function __construct(
        protected ApplicationInterface $app,
        protected Terminal $terminal = new Terminal()
    ) {
    }

    public function logRequestInfo(RequestInterface $request, int $statusCode, float $duration): void
    {
        $name = $this->app->getAppName();

        $terminalWidth = $this->getTerminalWidth();

        $method = $request->getMethod();
        $uri = $request->getUri()->getPath();
        $pid = (string) getmypid();
        $memory = number_format(memory_get_usage() / 1024 / 1024, 2) . 'MB';
        $durationText = number_format($duration) . 'ms';

        $color = match (true) {
            $statusCode >= 500 => 'red',
            $statusCode >= 400 => 'yellow',
            $statusCode >= 300 => 'cyan',
            $statusCode >= 100 => 'green',
            default => 'white',
        };

        $spaces = 33;
        $length = $spaces + strlen($method . $statusCode . $uri . $pid . $memory . $durationText);

        $dots = str_repeat('.', max($terminalWidth - $length, 0));

        $log = sprintf(
            '  [<fg=%s;options=bold>%s</>][%s] %s <fg=gray>%s</> [<fg=%s;options=bold>%s</>] ' .
            'pid: <fg=yellow>%s</> - %s - %s',
            $color,
            $method,
            $name,
            $uri,
            $dots,
            $color,
            $statusCode,
            $pid,
            $memory,
            $durationText,
        );

        CliServerRuntime::logLine($log);
    }

    public function logWebSocketOpen(
        WebSocketRequestInterface $request,
        float $duration
    ): void {
        $name = $this->app->getAppName();

        // $terminalWidth = $this->getTerminalWidth();

        $uri = $request->getUri()->getPath();
        $pid = (string) getmypid();
        $memory = number_format(memory_get_usage() / 1024 / 1024, 2) . 'MB';
        $durationText = number_format($duration) . 'ms';

        $log = sprintf(
            '  [OPEN][%s] (fd: %s) %s - pid: %s - %s - %s',
            $name,
            $request->getFd(),
            $uri,
            $pid,
            $memory,
            $durationText
        );

        CliServerRuntime::logLine($log);
    }

    public function logWebSocketClose(
        WebSocketRequestInterface $request,
        float $duration
    ): void {
        $name = $this->app->getAppName();

        // $terminalWidth = $this->getTerminalWidth();

        $uri = $request->getUri()->getPath();
        $pid = (string) getmypid();
        $memory = number_format(memory_get_usage() / 1024 / 1024, 2) . 'MB';
        $durationText = number_format($duration) . 'ms';

        $log = sprintf(
            '  [CLOSE][%s] (fd: %s) %s - pid: %s - %s - %s',
            $name,
            $request->getFd(),
            $uri,
            $pid,
            $memory,
            $durationText
        );

        CliServerRuntime::logLine($log);
    }

    public function logWebSocketMessageStart(WebSocketRequestInterface $request): void
    {
        $name = $this->app->getAppName();

        // $terminalWidth = $this->getTerminalWidth();

        $uri = $request->getUri()->getPath();
        $pid = (string) getmypid();

        $log = sprintf(
            '    [MSG START][%s] (fd: %s) %s "%s" - pid: %s',
            $name,
            $request->getFd(),
            $uri,
            Str::truncate($request->getData(), 100, '...'),
            $pid,
        );

        CliServerRuntime::logLine($log);
    }

    public function logWebSocketMessageEnd(WebSocketRequestInterface $request, int $duration): void
    {
        $name = $this->app->getAppName();

        // $terminalWidth = $this->getTerminalWidth();

        $pid = (string) getmypid();
        $memory = number_format(memory_get_usage() / 1024 / 1024, 2) . 'MB';
        $durationText = number_format($duration) . 'ms';

        $log = sprintf(
            '    [MSG END][%s] (fid: %s) - pid: %s - %s - %s',
            $name,
            $request->getFd(),
            $pid,
            $memory,
            $durationText,
        );

        CliServerRuntime::logLine($log);
    }

    protected function getTerminalWidth(int $min = 30): int
    {
        return $this->terminalWidth ??= max($this->terminal->getWidth(), $min);
    }
}
