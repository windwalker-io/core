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
    public function __construct(array $options = [])
    {
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
        if ($this->getOption('debug')) {
            echo $e;
        } else {
            echo $e->getMessage();
        }

        // $renderer = $this->app->renderer->getRenderer($this->engine);
        //
        // $body = $renderer->render(
        //     $this->app->get('error.template', 'windwalker.error.default'),
        //     ['exception' => $exception]
        // );
        //
        // $code = $exception->getCode();
        //
        // if ($code < 400 || $code >= 500) {
        //     $code = 500;
        // }
        //
        // $response = (new HtmlResponse($body))->withStatus($code);
        //
        // $this->app->server->getOutput()->respond($response);
    }
}
