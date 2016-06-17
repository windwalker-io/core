<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Renderer\Edge;

use Windwalker\Edge\Extension\EdgeExtensionInterface;

/**
 * The WindwalkerExtension class.
 *
 * @since  {DEPLOY_VERSION}
 */
class WindwalkerExtension implements EdgeExtensionInterface
{
	/**
	 * getName
	 *
	 * @return  string
	 */
	public function getName()
	{
		return 'windwalker';
	}

	/**
	 * getDirectives
	 *
	 * @return  callable[]
	 */
	public function getDirectives()
	{
		return [
			'lang'      => [$this, 'translate'],
			'translate' => [$this, 'translate'],
			'sprintf'   => [$this, 'translate'],
			'plural'    => [$this, 'plural'],
			'choice'    => [$this, 'plural'],
			'messages'  => [$this, 'messages'],
			'widget'    => [$this, 'widget'],
		];
	}

	/**
	 * getGlobals
	 *
	 * @return  array
	 */
	public function getGlobals()
	{
		return [];
	}

	/**
	 * getParsers
	 *
	 * @return  callable[]
	 */
	public function getParsers()
	{
		return [];
	}

	/**
	 * translate
	 *
	 * @param   string  $expression
	 *
	 * @return  string
	 */
	public function translate($expression)
	{
		return "<?php echo \$translator->translate{$expression} ?>";
	}

	/**
	 * sprintf
	 *
	 * @param   string  $expression
	 *
	 * @return  string
	 */
	public function sprintf($expression)
	{
		return "<?php echo \$translator->sprintf{$expression} ?>";
	}
	
	/**
	 * plural
	 *
	 * @param   string  $expression
	 *
	 * @return  string
	 */
	public function plural($expression)
	{
		return "<?php echo \$translator->plural{$expression} ?>";
	}
	
	/**
	 * widget
	 *
	 * @param   string  $expression
	 *
	 * @return  string
	 */
	public function widget($expression)
	{
		return "<?php echo \\Windwalker\\Core\\Widget\\BladeWidgetHelper::render{$expression} ?>";
	}
	
	/**
	 * messages
	 *
	 * @param   string  $expression
	 *
	 * @return  string
	 */
	public function messages($expression)
	{
		return "<?php echo \\Windwalker\\Core\\Widget\\WidgetHelper::render('windwalker.message.default', array('messages' => \$messages)) ?>";
	}
}
