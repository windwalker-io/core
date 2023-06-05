<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Error;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Throwable;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Renderer\RendererService;
use Windwalker\Core\Service\ErrorService;
use Windwalker\DI\Container;
use Windwalker\Http\Output\Output;
use Windwalker\Http\Output\OutputInterface;
use Windwalker\Http\Output\StreamOutput;
use Windwalker\Http\Response\HtmlResponse;
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
        protected ApplicationInterface $app,
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
     * @param  Throwable  $e
     *
     * @return  void
     */
    public function __invoke(Throwable $e): void
    {
        $renderer = $this->app->service(RendererService::class);

        try {
            $html = $renderer->render(
                $this->options['layout'],
                [
                    'app' => $this->app,
                    'exception' => $e,
                ]
            );
        } catch (\Throwable $e) {
            echo $e;

            return;
        }

        $code = $e->getCode();

        $code = ErrorService::normalizeCode($code);

        $container = $this->app->getContainer();

        if ($container->has(OutputInterface::class)) {
            $output = $container->get(OutputInterface::class);
        } else {
            $output = new StreamOutput();
        }

        $output->respond(HtmlResponse::fromString($html, $code));
    }
}
