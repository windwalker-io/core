<?php

/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\CliServer;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Terminal;
use Windwalker\Core\Application\ApplicationInterface;

use function Windwalker\chronos;

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
        $memory = number_format(memory_get_usage() / 1024 / 1024, 2) . 'MB';
        $durationText = number_format($duration) . 'ms';

        $color = match (true) {
            $statusCode >= 500 => 'red',
            $statusCode >= 400 => 'yellow',
            $statusCode >= 300 => 'cyan',
            $statusCode >= 100 => 'green',
            default => 'white',
        };

        $spaces = 25;
        $length = $spaces + strlen($method . $statusCode . $uri . $memory . $durationText);

        $dots = str_repeat('.', $terminalWidth - $length);

        $log = sprintf(
            '  [<fg=%s;options=bold>%s</>][%s] %s <fg=gray>%s</> [<fg=%s;options=bold>%s</>] %s - %s',
            $color,
            $method,
            $name,
            $uri,
            $dots,
            $color,
            $statusCode,
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
