<?php

declare(strict_types=1);

namespace Windwalker\Core\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Windwalker\Core\Http\CorsHandler;
use Windwalker\Core\Manager\Logger;
use Windwalker\DI\Container;
use Windwalker\Http\Response\Response;
use Windwalker\Utilities\Arr;
use Windwalker\Utilities\Options\OptionsResolverTrait;

/**
 * The CorsMiddleware class.
 */
class CorsMiddleware implements MiddlewareInterface
{
    use OptionsResolverTrait;

    public function __construct(
        protected Container $container,
        protected bool $enabled = true,
        protected string|array|null $allowOrigins = null,
        protected bool $sendInstantly = false,
        protected ?\Closure $configure = null,
        /**
         * @deprecated  Use constructor arguments instead.
         */
        array $options = [],
    ) {
        $this->resolveOptions($options, [$this, 'configureOptions']);
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->define('enabled')
            ->allowedTypes('bool', 'callable')
            ->default($this->enabled);

        $resolver->define('allow_origins')
            ->allowedTypes('string', 'array', 'null')
            ->default($this->allowOrigins ?? env('CORS_ALLOW_ORIGINS') ?: null);

        $resolver->define('configure')
            ->allowedTypes('callable', 'null')
            ->default($this->configure);

        $resolver->define('send_instantly')
            ->allowedTypes('bool')
            ->default($this->sendInstantly);
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
            $cors = $this->getCorsHandler($request);

            if ($this->options['send_instantly']) {
                $this->sendInstantly($cors);

                if ($request->getMethod() === 'OPTIONS') {
                    return Response::fromString('');
                }
            } else {
                try {
                    if ($request->getMethod() === 'OPTIONS') {
                        $res = Response::fromString('');
                    } else {
                        $res = $handler->handle($request);
                    }

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

        $allows = $this->options['allow_origins'];

        if (is_string($allows)) {
            $allows = Arr::explodeAndClear(',', $allows);
        }

        $allows = (array) $allows;

        $allowOrigin = '';

        if (!$origin) {
            // If old browser not support origin header in request,
            // Must allow all.
            $allowOrigin = '*';
        } else {
            if (count($allows) === 1) {
                // If only 1 allows, always send this as allow origin
                if ($allows[0] === 'ORIGIN') {
                    $allowOrigin = $origin;
                } else {
                    $allowOrigin = $allows[0];
                }
            } elseif (count($allows) > 1) {
                // Is has multiple allow, check origin in allow list
                if (in_array($origin, $allows, true)) {
                    $allowOrigin = $origin;
                }
            }
        }

        $cors = CorsHandler::create();

        if ($allowOrigin) {
            $cors->allowOrigin($allowOrigin);
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
