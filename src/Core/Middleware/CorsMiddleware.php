<?php

/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2022 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Windwalker\Core\Http\CorsHandler;
use Windwalker\DI\Container;
use Windwalker\Http\Response\Response;
use Windwalker\Utilities\Options\OptionsResolverTrait;

/**
 * The CorsMiddleware class.
 */
class CorsMiddleware implements MiddlewareInterface
{
    use OptionsResolverTrait;

    public function __construct(protected Container $container, array $options = [])
    {
        $this->resolveOptions($options, [$this, 'configureOptions']);
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->define('enabled')
            ->allowedTypes('bool', 'callable')
            ->default(true);

        $resolver->define('allow_origins')
            ->allowedTypes('string', 'array')
            ->default(env('CORS_ALLOW_ORIGINS') ?: null);

        $resolver->define('configure')
            ->allowedTypes('callable');

        $resolver->define('send_instantly')
            ->allowedTypes('bool')
            ->default(false);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $enabled = $this->options['enabled'];

        if (is_callable($enabled)) {
            $enabled = $this->container->call(
                $enabled,
                [
                    ServerRequestInterface::class => $request,
                    'request' => $request,
                    'options' => $this->options
                ]
            );
        }

        if ($enabled) {
            if ($request->getMethod() === 'OPTIONS') {
                return Response::fromString('');
            }

            $cors = $this->getCorsHandler($request);

            if ($this->options['send_instantly']) {
                $this->sendInstantly($cors);
            } else {
                try {
                    $res = $handler->handle($request);

                    return $cors->handle($res);
                } catch (\Throwable $e) {
                    $this->sendInstantly($cors);

                    throw $e;
                }
            }
        }

        return $handler->handle($request);
    }

    public function getCorsHandler(ServerRequestInterface $request): CorsHandler
    {
        $origin = $request->getHeaderLine('origin');

        $allows = (array) $this->options['allow_origins'];

        if (!$origin) {
            // If old browser not support origin header in request,
            // Must allow all.
            $origin = '*';
        } else {
            if (count($allows) === 1) {
                // If only 1 allows, always send this as allow origin
                $origin = $allows[0];
            } elseif (count($allows) > 1) {
                // Is has multiple allow, check origin in allow list
                if (!in_array($origin, $allows, true)) {
                    $origin = null;
                }
            } else {
                // No matched, do not allow any.
                $origin = '';
            }
        }

        $cors = CorsHandler::create();

        if ($origin) {
            $cors->allowOrigin($origin);
        }

        $configure = $this->options['configure'];

        if (is_callable($configure)) {
            $cors = $this->container->call(
                $configure,
                [
                    'cors' => $cors,
                    CorsHandler::class => $cors
                ]
            ) ?? $cors;
        }

        return $cors;
    }

    public function sendInstantly(CorsHandler $cors): void
    {
        $response = $cors->getResponse();

        foreach ($response->getHeaders() as $header => $headers) {
            foreach ($headers as $value) {
                header("$header: $value");
            }
        }
    }
}
