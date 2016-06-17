<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Provider;

use Illuminate\View\Compilers\BladeCompiler;
use Windwalker\Core\Renderer\Finder\PackageFinder;
use Windwalker\Core\Renderer\RendererFactory;
use Windwalker\Core\View\Twig\WindwalkerExtension;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Renderer;

/**
 * The TemplateEngineProvider class.
 *
 * @since  2.1.1
 */
class TemplateEngineProvider implements ServiceProviderInterface
{
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
			return new RendererFactory($container);
		};

		$container->share('renderer.factory', $closure);

		$closure = function(Container $container)
		{
			return new PackageFinder($container->get('package.resolver'));
		};

		$container->share('package.finder', $closure);
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

		Renderer\Blade\GlobalContainer::setCachePath($container->get('system.config')->get('path.cache') . '/view');
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
}
