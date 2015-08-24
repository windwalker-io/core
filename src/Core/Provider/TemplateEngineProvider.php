<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Provider;

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
		// Blade
		Renderer\Blade\GlobalContainer::addCompiler('translate', function($expression)
		{
			return "<?php echo \$language->translate{$expression} ?>";
		});

		Renderer\Blade\GlobalContainer::addCompiler('sprintf', function($expression)
		{
			return "<?php echo \$language->sprintf{$expression} ?>";
		});

		Renderer\Blade\GlobalContainer::addCompiler('plural', function($expression)
		{
			return "<?php echo \$language->plural{$expression} ?>";
		});

		Renderer\Blade\GlobalContainer::setCachePath($container->get('system.config')->get('path.cache') . '/view');

		// Twig
		Renderer\Twig\GlobalContainer::addExtension('windwalker', new WindwalkerExtension($container));
	}
}
