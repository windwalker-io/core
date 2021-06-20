<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Error;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Windwalker\Core\Renderer\RendererService;
use Windwalker\Core\Service\ErrorService;
use Windwalker\DI\Container;
use Windwalker\Http\Output\OutputInterface;
use Windwalker\Http\Output\StreamOutput;
use Windwalker\Http\Response\HtmlResponse;
use Windwalker\Http\Server\HttpServer;
use Windwalker\Http\Server\ServerInterface;
use Windwalker\Utilities\Options\OptionsResolverTrait;

/**
 * The SimpleErrorPageHandler class.
 */
class SimpleErrorPageHandler implements ErrorHandlerInterface
{
    use OptionsResolverTrait;

    /**
     * SimpleErrorPageHandler constructor.
     *
     * @param  array  $options
     */
    public function __construct(
        protected Container $container,
        array $options = []
    ) {
        $this->resolveOptions($options, [$this, 'configureOptions']);
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'debug' => false,
                'layout' => 'windwalker.error.simple',
            ]
        );
    }

    /**
     * __invoke
     *
     * @param  \Throwable  $e
     *
     * @return  void
     */
    public function __invoke(\Throwable $e): void
    {
        $renderer = $this->container->get(RendererService::class);

        $html = $renderer->render(
            $this->options['layout'],
            ['exception' => $e]
        );

        $code = $e->getCode();

        $code = ErrorService::normalizeCode($code);

        $output = new StreamOutput();
        $output->respond(HtmlResponse::fromString($html, $code));
    }
}
