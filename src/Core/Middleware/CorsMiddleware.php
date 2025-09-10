<?php

declare(strict_types=1);

namespace Windwalker\Core\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
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
        protected \Closure|LoggerInterface|string|null $logger = null,
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

                    $res = $cors->handle($res);

                    $log = $this->getLogCallback();

                    if ($log) {
                        $log('Response CORS: ' . json_encode(static::getHeaders($res), JSON_PRETTY_PRINT));
                    }

                    return $res;
                } catch (\Throwable $e) {
                    $log = $this->getLogCallback();

                    if ($log) {
                        $log('Something Error, send CORS instantly: ' . $e->getMessage());
                    }

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

        $log = $this->getLogCallback();

        foreach ($response->getHeaders() as $header => $headers) {
            foreach ($headers as $value) {
                header("$header: $value");
            }
        }

        $log && $log('Send CORS headers instantly: ' . json_encode(static::getHeaders($response), JSON_PRETTY_PRINT));
    }

    protected static function getHeaders(ResponseInterface $response): array
    {
        return Arr::mapWithKeys(
            $response->getHeaders(),
            static fn ($v, $k) => yield $k => $response->getHeaderLine($k)
        );
    }

    protected function getLogCallback(): ?\Closure
    {
        if ($this->logger instanceof \Closure) {
            return $this->logger;
        }

        if (is_string($this->logger)) {
            return function ($message, $context = []) {
                $this->container->get(LoggerInterface::class, tag: $this->logger)
                    ->debug($message, $context);
            };
        }

        if ($this->logger instanceof LoggerInterface) {
            return function ($message, $context = []) {
                $this->logger->debug($message, $context);
            };
        }

        return null;
    }
}
