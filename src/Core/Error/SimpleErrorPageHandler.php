<?php

declare(strict_types=1);

namespace Windwalker\Core\Error;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
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
     * @param  ApplicationInterface  $app
     * @param  array                 $options
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
     * @param  Throwable  $e
     *
     * @return  void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(Throwable $e): void
    {
        // Todo: error provider is early than renderer provider, sometimes
        // RendererService may not exists here.
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
