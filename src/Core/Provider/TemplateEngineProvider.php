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

		$this->prepareExtends($container);
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
	 * prepareExtends
	 *
	 * @param Container $container
	 *
	 * @return  void
	 */
	protected function prepareExtends(Container $container)
	{
		// Blade
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

		// B/C for 4.*
		if (!method_exists('Illuminate\View\Compilers\BladeCompiler', 'directive'))
		{
			Renderer\Blade\GlobalContainer::setContentTags('{{', '}}');
			Renderer\Blade\GlobalContainer::setEscapedTags('{{{', '}}}');

			Renderer\Blade\GlobalContainer::addExtension('rawTag', function($view, BladeCompiler $compiler)
			{
				$pattern = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', '{!!', '!!}');

				$callback = function ($matches) use ($compiler)
				{
					$whitespace = empty($matches[3]) ? '' : $matches[3].$matches[3];

					return $matches[1] ? substr($matches[0], 1) : '<?php echo ' . $compiler->compileEchoDefaults($matches[2]).'; ?>' . $whitespace;
				};

				return preg_replace_callback($pattern, $callback, $view);
			});
		}

		// Twig
		if (class_exists('Twig_Extension'))
		{
			Renderer\Twig\GlobalContainer::addExtension('windwalker', new WindwalkerExtension($container));
		}
	}
}
