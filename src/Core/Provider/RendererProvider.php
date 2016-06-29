<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Provider;

use Illuminate\View\Compilers\BladeCompiler;
use Windwalker\Core\DateTime\DateTime;
use Windwalker\Core\Event\EventDispatcher;
use Windwalker\Core\Renderer\Finder\PackageFinder;
use Windwalker\Core\Renderer\RendererManager;
use Windwalker\Core\Renderer\Twig\WindwalkerExtension;
use Windwalker\Core\Widget\WidgetManager;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Renderer;
use Windwalker\Session\Session;

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
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container $container The DI container.
	 *
	 * @return  void
	 */
	public function register(Container $container)
	{
		$this->prepareManager($container);

		$this->getWidgetManager($container);
	}

	/**
	 * prepareFactory
	 *
	 * @param Container $container
	 *
	 * @return  void
	 */
	protected function prepareManager(Container $container)
	{
		$closure = function(Container $container)
		{
			$manager = new RendererManager($container);

			return $manager;
		};

		$container->share('raw.renderer.manager', $closure);

		$closure = function (Container $container)
		{
			$manager = $container->get('raw.renderer.manager');

			// Prepare Globals
			$this->prepareGlobals($container, $manager);

			return $manager;
		};

		$container->share(RendererManager::class, $closure);

		$closure = function(Container $container)
		{
			return new PackageFinder($container->get('package.resolver'));
		};

		$container->share(PackageFinder::class, $closure);
	}

	/**
	 * prepareWidget
	 *
	 * @param Container       $container
	 *
	 * @return WidgetManager
	 */
	public function getWidgetManager(Container $container)
	{
		$closure = function (Container $container)
		{
			/** @var RendererManager $rendererManager */
			$rendererManager = $container->get('raw.renderer.manager');

			return new WidgetManager($rendererManager);
		};

		$container->share(WidgetManager::class, $closure);
	}

	/**
	 * boot
	 *
	 * @param Container $container
	 *
	 * @return  void
	 */
	public function boot(Container $container)
	{
		$this->prepareBlade($container);
		$this->prepareEdge($container);
		$this->prepareTwig($container);
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
		if (!class_exists('Illuminate\View\Compilers\BladeCompiler'))
		{
			return;
		}

		Renderer\Blade\GlobalContainer::addCompiler('translate', function($expression)
		{
			return "<?php echo \$translator->translate{$expression} ?>";
		});

		Renderer\Blade\GlobalContainer::addCompiler('sprintf', function($expression)
		{
			return "<?php echo \$translator->sprintf{$expression} ?>";
		});

		Renderer\Blade\GlobalContainer::addCompiler('plural', function($expression)
		{
			return "<?php echo \$translator->plural{$expression} ?>";
		});

		Renderer\Blade\GlobalContainer::addCompiler('widget', function($expression)
		{
			return "<?php echo \$widget->render{$expression} ?>";
		});

		Renderer\Blade\GlobalContainer::addCompiler('messages', function($expression)
		{
			return "<?php echo \$widget->render('windwalker.message.default', array('messages' => \$messages), 'php', \$package) ?>";
		});

		Renderer\Blade\GlobalContainer::addCompiler('route', function($expression)
		{
			return "<?php echo \$route->encode{$expression} ?>";
		});

		Renderer\Blade\GlobalContainer::addCompiler('assetTemplate', function($expression)
		{
			return "<?php \$asset->getTemplate->startTemplate{$expression} ?>";
		});

		Renderer\Blade\GlobalContainer::addCompiler('endTemplate', function($expression)
		{
			return "<?php \$asset->getTemplate->endTemplate{$expression} ?>";
		});

		Renderer\Blade\GlobalContainer::addCompiler('formToken', function($expression)
		{
			return "<?php echo \\Windwalker\\Core\\Security\\CsrfProtection::input{$expression} ?>";
		});

		Renderer\Blade\GlobalContainer::setCachePath($container->get('config')->get('path.cache') . '/view');
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
		Renderer\Edge\GlobalContainer::addExtension(new \Windwalker\Core\Renderer\Edge\WindwalkerExtension);
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
		if (class_exists('Twig_Extension'))
		{
			Renderer\Twig\GlobalContainer::addExtension('windwalker', new WindwalkerExtension($container));
		}
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
		if (static::$messages === null)
		{
			/** @var Session $session */
			$session = $container->get('session');

			$session->start();

			static::$messages = $session->getFlashBag()->takeAll();
		}

		$globals = array(
			'uri'        => $container->get('uri'),
			'app'        => $container->get('application'),
			'asset'      => $container->get('asset'),
			'messages'   => static::$messages,
			'translator' => $container->get('language'),
			'widget'     => $container->get('widget.manager'),
			'datetime'   => new DateTime('now', new \DateTimeZone($container->get('config')->get('system.timezone', 'UTC')))
		);

		$manager->setGlobals($globals);

		/** @var EventDispatcher $dispatcher */
		$dispatcher = $container->get('dispatcher');

		$dispatcher->triggerEvent('onRendererPrepareGlobals', [
			'manager' => $manager
		]);

		return $manager;
	}
}
