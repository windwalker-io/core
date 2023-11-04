<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Application;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Windwalker\Core\CliServer\CliServerRuntime;
use Windwalker\Core\Manager\LoggerManager;
use Windwalker\Core\Router\RouteUri;
use Windwalker\Http\Response\RedirectResponse;
use Windwalker\Http\Response\Response;

/**
 * Trait WebApplicationTrait
 */
trait WebApplicationTrait
{
    use ApplicationTrait;

    /**
     * Method to check to see if headers have already been sent.
     * We wrap headers_sent() function with this method for testing reason.
     *
     * @return  boolean  True if the headers have already been sent.
     *
     * @see     headers_sent()
     *
     * @since   3.0
     */
    public function checkHeadersSent(): bool
    {
        return headers_sent();
    }

    public function getClient(): AppClient
    {
        return AppClient::WEB;
    }

    public function log(string|\Stringable $message, array $context = [], string $level = LogLevel::INFO): static
    {
        if ($this->isCliRuntime()) {
            CliServerRuntime::logLine($message);
        }

        $this->logger ??= $this->getLogger();

        $this->logger->log($level, (string) $message, $context);

        return $this;
    }

    private function getLogger(): LoggerInterface
    {
        $manager = $this->container->get(LoggerManager::class);

        $name = 'web';

        if ($this->isCliRuntime()) {
            $name = 'cli-web';
        }

        if (!$manager->has($name)) {
            return new NullLogger();
        }

        return $manager->get($name);
    }

    /**
     * @param  mixed  $res
     *
     * @return ResponseInterface
     *
     * @throws \JsonException
     * @since  4.0
     */
    public static function anyToResponse(mixed $res): ResponseInterface
    {
        if ($res instanceof RouteUri) {
            return $res->toResponse();
        }

        if ($res instanceof UriInterface) {
            return new RedirectResponse($res);
        }

        if (!$res instanceof ResponseInterface) {
            if (is_array($res) || is_object($res)) {
                return Response::fromString(json_encode($res, JSON_THROW_ON_ERROR));
            }

            return Response::fromString((string) $res);
        }

        return $res;
    }
}
