<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2020 .
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Application;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Windwalker\Core\Widget\WidgetHelper;
use Windwalker\DI\Annotation\Inject;
use Windwalker\Http\Response\HtmlResponse;
use Windwalker\Structure\Structure;

/**
 * The OfflineService class.
 *
 * @since  __DEPLOY_VERSION__
 */
class OfflineService
{
    /**
     * @Inject()
     *
     * @var WebApplication
     */
    protected $app;

    /**
     * offline
     *
     * @param array|Structure $options
     *
     * @return  void
     *
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     * @since  __DEPLOY_VERSION__
     */
    public function offlineIfEnabled(array $options): void
    {
        $options = new Structure($options);

        $app = $this->app;

        $enabled = $app->isOffline() && !$app->get('system.debug');
        $enabled = $options->get('enabled', $enabled);
        $enabled = is_callable($enabled) ? $app->getContainer()->call($enabled) : $enabled;

        if (!$enabled) {
            return;
        }

        if ($this->matchTester($app, $options)) {
            return;
        }

        if ($options->get('only_view')) {
            $app->listen(
                'onViewBeforeRender',
                function () use ($options) {
                    echo $this->renderOfflinePage($options);
                    die;
                }
            );
        } else {
            $this->offline($app, $options);
        }
    }

    /**
     * offline
     *
     * @param WebApplication $app
     * @param Structure      $options
     *
     * @return  void
     *
     * @since  __DEPLOY_VERSION__
     */
    public function offline(WebApplication $app, Structure $options): void
    {
        $self = $this;

        $app->server->setHandler(
            function (
                RequestInterface $request,
                ResponseInterface $response,
                callable $next = null
            ) use (
                $options,
                $self
            ) {
                return new HtmlResponse(
                    $self->renderOfflinePage($options)
                );
            }
        );

        $app->server->listen();

        die;
    }

    /**
     * renderOfflinePage
     *
     * @param Structure $options
     *
     * @return string
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     * @since  __DEPLOY_VERSION__
     */
    public function renderOfflinePage(Structure $options): string
    {
        $renderer = $options['renderer'] ?? null;

        if (is_callable($renderer)) {
            return $this->app->getContainer()->call($renderer);
        }

        return WidgetHelper::render(
            $options['template'] ?? 'windwalker.offline.offline',
            $options['data'] ?? [],
            $options['engine'] ?? 'php'
        );
    }

    /**
     * matchTester
     *
     * @param WebApplication $app
     * @param Structure      $options
     *
     * @return  bool
     *
     * @since  3.5.12.1
     */
    protected function matchTester(WebApplication $app, Structure $options): bool
    {
        if ($options['tester'] === null) {
            return false;
        }

        return $app->input->header->get('x-tester') === $options['tester'];
    }
}
