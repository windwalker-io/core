<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Provider;

use Illuminate\View\Compilers\BladeCompiler;
use Windwalker\Core\Event\EventDispatcher;
use Windwalker\Core\Renderer\Finder\PackageFinder;
use Windwalker\Core\Renderer\RendererManager;
use Windwalker\Core\Renderer\Twig\WindwalkerExtension;
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
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container $container The DI container.
	 *
	 * @return  void
	 */
	public function register(Container $container)
	{
		$this->prepareFactory($container);
	}

	/**
	 * prepareFactory
	 *
	 * @param Container $container
	 *
	 * @return  void
	 */
	protected function prepareFactory(Container $container)
	{
		$closure = function(Container $container)
		{
			$manager = new RendererManager($container);

			// Prepare Globals
			$this->prepareGlobals($container, $manager);

			return $manager;
		};

		$container->share(RendererManager::class, $closure)
			->alias('renderer.manager', RendererManager::class)
			->alias('renderer', RendererManager::class);

		$closure = function(Container $container)
		{
			return new PackageFinder($container->get('package.resolver'));
		};

		$container->share(PackageFinder::class, $closure)
			->alias('package.finder', PackageFinder::class);
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
			return "<?php echo \\Windwalker\\Core\\Widget\\BladeWidgetHelper::render{$expression} ?>";
		});

		Renderer\Blade\GlobalContainer::addCompiler('messages', function($expression)
		{
			return "<?php echo \\Windwalker\\Core\\Widget\\WidgetHelper::render('windwalker.message.default', array('flashes' => \$flashes)) ?>";
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
			static::$messages = $container->get('session')
				->getFlashBag()
				->takeAll();
		}

		$globals = array(
			'uri'        => $container->get('uri'),
			'app'        => $container->get('application'),
			'asset'      => $container->get('asset'),
			'messages'   => static::$messages,
			'translator' => $container->get('language'),
			'datetime'   => new \DateTime('now', new \DateTimeZone($container->get('config')->get('system.timezone', 'UTC')))
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
