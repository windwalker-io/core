<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Provider;

use Illuminate\View\Compilers\BladeCompiler;
use Windwalker\Core\DateTime\Chronos;
use Windwalker\Core\Event\EventDispatcher;
use Windwalker\Core\Renderer\Edge\WindwalkerExtension as EdgeExtension;
use Windwalker\Core\Renderer\Finder\PackageFinder;
use Windwalker\Core\Renderer\RendererManager;
use Windwalker\Core\Renderer\Twig\WindwalkerExtension;
use Windwalker\Core\Widget\WidgetManager;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Renderer;

/**
 * The TemplateEngineProvider class.
 *
 * @since  2.1.1
 */
class RendererProvider implements ServiceProviderInterface
{
    /**
     * Property messages.
     *
     * @var  array
     */
    protected static $messages;

    /**
     * Property rendererManager.
     *
     * @var  RendererManager
     */
    public $rendererManager;

    /**
     * boot
     *
     * @param Container $container
     *
     * @return  void
     */
    public function bootDeferred(Container $container)
    {
        $this->prepareBlade($container);
        $this->prepareEdge($container);
        $this->prepareTwig($container);
    }

    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container $container The DI container.
     *
     * @return  void
     */
    public function register(Container $container)
    {
        $container->prepareSharedObject(RendererManager::class, [$this, 'manager']);
        $container->prepareSharedObject(PackageFinder::class);
        $container->share(WidgetManager::class, [$this, 'widget']);
    }

    /**
     * prepareFactory
     *
     * @param Container       $container
     * @param RendererManager $manager
     *
     * @return RendererManager
     */
    public function manager(RendererManager $manager, Container $container)
    {
        $this->rendererManager = $manager;

        // Prepare Globals
        $this->prepareGlobals($container, $manager);

        return $manager;
    }

    /**
     * prepareWidget
     *
     * @param Container $container
     *
     * @return WidgetManager
     */
    public function widget(Container $container)
    {
        /** @var RendererManager $rendererManager */
        $rendererManager = $this->getRendererManager($container);

        return new WidgetManager($rendererManager);
    }

    /**
     * getRendererManager
     *
     * @param Container $container
     *
     * @return  RendererManager
     */
    public function getRendererManager(Container $container)
    {
        if ($this->rendererManager) {
            return $this->rendererManager;
        }

        return $container->get(RendererManager::class);
    }

    /**
     * prepareGlobals
     *
     * @param Container       $container
     * @param RendererManager $manager
     *
     * @return  RendererManager
     */
    protected function prepareGlobals(Container $container, RendererManager $manager)
    {
        $globals = [
            'uri' => $container->exists('uri') ? $container->get('uri') : null,
            'app' => $container->get('application'),
            'asset' => $container->exists('asset') ? $container->get('asset') : null,
            'messages' => [], // Deprecated this variable after 4.0
            'translator' => $container->exists('language') ? $container->get('language') : null,
            'widget' => $container->exists('widget.manager') ? $container->get('widget.manager') : null,
            'datetime' => new Chronos('now',
                new \DateTimeZone($container->get('config')->get('system.timezone', 'UTC'))),
        ];

        $manager->setGlobals($globals);

        /** @var EventDispatcher $dispatcher */
        if ($container->exists('dispatcher')) {
            $dispatcher = $container->get('dispatcher');

            $dispatcher->triggerEvent('onRendererPrepareGlobals', [
                'manager' => $manager,
            ]);
        }

        return $manager;
    }

    /**
     * prepareEdge
     *
     * @param Container $container
     *
     * @return  void
     */
    protected function prepareEdge(Container $container)
    {
        Renderer\Edge\GlobalContainer::addExtension(new EdgeExtension);
    }

    /**
     * prepareTwig
     *
     * @param Container $container
     *
     * @return  void
     */
    protected function prepareTwig(Container $container)
    {
        // Twig
        if (class_exists('Twig_Extension')) {
            Renderer\Twig\GlobalContainer::addExtension('windwalker', new WindwalkerExtension($container));
        }
    }

    /**
     * prepareBlade
     *
     * @param Container $container
     *
     * @return  void
     */
    protected function prepareBlade(Container $container)
    {
        if (!class_exists('Illuminate\View\Compilers\BladeCompiler')) {
            return;
        }

        $extension = new EdgeExtension;

        $directives = [
            'translate' => [$extension, 'translate'],
            'sprintf' => [$extension, 'sprintf'],
            'plural' => [$extension, 'plural'],
            'messages' => [$extension, 'messages'],
            'widget' => [$extension, 'widget'],
            'route' => [$extension, 'route'],
            'formToken' => [$extension, 'formToken'],

            // Authorisation
            'auth' => [$extension, 'auth'],
            'endauth' => [$extension, 'endauth'],

            // Asset
            'css' => [$this, 'css'],
            'js' => [$this, 'js'],
            'assetTemplate' => [$extension, 'assetTemplate'],
            'endTemplate' => [$extension, 'endTemplate'],
            'attr' => [$this, 'attr'],

            // Debug
            'shown' => [$this, 'shown'],
            'die' => [$this, 'dead'],
            'debug' => [$this, 'debug'],
            'enddebug' => [$this, 'endauth'],
        ];

        foreach ($directives as $name => $callback) {
            Renderer\Blade\GlobalContainer::addCompiler($name, $callback);
        }

        Renderer\Blade\GlobalContainer::setCachePath($container->get('config')->get('path.cache') . '/view');
    }

    /**
     * Method to get property Messages
     *
     * @return  array
     */
    public static function getMessages()
    {
        return static::$messages;
    }
}
