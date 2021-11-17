<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Html\HtmlFrame;
use Windwalker\DI\Attributes\Inject;

/**
 * The MetadataMiddleware class.
 */
class MetadataMiddleware implements MiddlewareInterface
{
    #[Inject]
    protected AppContext $app;

    /**
     * @var callable|null
     */
    protected $handler;

    public function __construct(
        protected array $meta = [],
        protected array $og = [],
        ?callable $handler = null
    ) {
        $this->handler = $handler;
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $htmlFrame = $this->app->service(HtmlFrame::class);

        if ($this->meta) {
            foreach ($this->meta as $name => $content) {
                $content = (array) $content;

                foreach ($content as $c) {
                    $htmlFrame->addMetadata($name, $c);
                }
            }
        }

        if ($this->og) {
            foreach ($this->og as $name => $content) {
                $content = (array) $content;

                foreach ($content as $c) {
                    $htmlFrame->addOpenGraph($name, $c);
                }
            }
        }

        if ($this->handler) {
            $this->app->call($this->handler, ['htmlFrame' => $htmlFrame]);
        }

        return $handler->handle($request);
    }
}
