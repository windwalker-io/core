<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\SystemPackage\Listener;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Widget\WidgetHelper;
use Windwalker\Debugger\Helper\DebuggerHelper;
use Windwalker\Event\Event;
use Windwalker\Http\Response\HtmlResponse;
use Windwalker\Structure\Structure;
use Windwalker\Utilities\Classes\OptionAccessTrait;

/**
 * The SystemListener class.
 *
 * NOTE: This listener has been registered after onAfterInitialise event. So the first event is onAfterRouting.
 *
 * @since  2.1.1
 */
class SystemListener
{
    use OptionAccessTrait;

    /**
     * SystemListener constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * onAfterInitialise
     *
     * @param Event $event
     *
     * @return  void
     */
    public function onAfterInitialise(Event $event): void
    {
        /** @var WebApplication $app */
        $app = $event['app'];

        // Remove index.php
        if ($app->uri->script === 'index.php') {
            $app->uri->script = null;
        }
    }

    /**
     * onBeforeRouting
     *
     * @param Event $event
     *
     * @return  void
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     */
    public function onBeforeRouting(Event $event): void
    {
        /** @var WebApplication $app */
        $app = $event['app'];

        $options = new Structure($this->getOption('offline'), 'json', ['load_raw' => true]);

        if (!$options->get('only_view')) {
            $this->offline($app, $options);
        }
    }

    /**
     * onViewBeforeRender
     *
     * @param Event $event
     *
     * @return  void
     *
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     * @since  __DEPLOY_VERSION__
     */
    public function onViewBeforeRender(Event $event): void
    {
        /** @var WebApplication $app */
        $app = $event['view']->getPackage()->app;

        $options = new Structure($this->getOption('offline'), 'json', ['load_raw' => true]);

        if ($options->get('only_view')) {
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
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     * @since  __DEPLOY_VERSION__
     */
    protected function offline(WebApplication $app, Structure $options): void
    {
        if ($app->isOffline()
            && !$app->get('system.debug')) {
            if ($this->matchTester($app, $options)) {
                return;
            }

            if ($options['condition']
                && is_callable($options['condition'])
                && !$app->getContainer()->call($options['condition'])) {
                return;
            }

            $app->server->setHandler(
                function (
                    RequestInterface $request,
                    ResponseInterface $response,
                    callable $next = null
                ) use ($options) {
                    return new HtmlResponse(
                        WidgetHelper::render(
                            $options['template'] ?? 'windwalker.offline.offline',
                            $options['data'] ?? [],
                            $options['engine'] ?? 'php'
                        )
                    );
                }
            );

            $app->server->listen();

            die;
        }
    }

    /**
     * matchTester
     *
     * @param WebApplication $app
     * @param Structure      $options
     *
     * @return  bool
     *
     * @since  __DEPLOY_VERSION__
     */
    protected function matchTester(WebApplication $app, Structure $options): bool
    {
        if ($options['tester'] === null) {
            return false;
        }

        return $app->input->header->get('x-tester') === $options['tester'];
    }
}
