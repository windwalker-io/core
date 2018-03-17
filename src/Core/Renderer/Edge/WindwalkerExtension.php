<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Renderer\Edge;

use Windwalker\Edge\Extension\EdgeExtensionInterface;

include_once __DIR__ . '/lastfix.php';

/**
 * The WindwalkerExtension class.
 *
 * @since  3.0
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
            'lang' => [$this, 'translate'],
            'translate' => [$this, 'translate'],
            'sprintf' => [$this, 'sprintf'],
            'plural' => [$this, 'plural'],
            'choice' => [$this, 'plural'],
            'messages' => [$this, 'messages'],
            'widget' => [$this, 'widget'],
            'route' => [$this, 'route'],
            'formToken' => [$this, 'formToken'],

            // Authorisation
            'auth' => [$this, 'auth'],
            'can' => [$this, 'auth'],
            'cannot' => [$this, 'cannot'],
            'endcan' => [$this, 'endauth'],
            'endcannot' => [$this, 'endauth'],
            'endauth' => [$this, 'endauth'],

            // Asset
            'css' => [$this, 'css'],
            'js' => [$this, 'js'],
            'assetTemplate' => [$this, 'assetTemplate'],
            'endTemplate' => [$this, 'endTemplate'],

            // Debug
            'shown' => [$this, 'shown'],
            'dd' => [$this, 'dd'],
            'die' => [$this, 'dead'],
            'debug' => [$this, 'debug'],
            'enddebug' => [$this, 'endauth'],
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
     * @param   string $expression
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
     * @param   string $expression
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
     * @param   string $expression
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
     * @param   string $expression
     *
     * @return  string
     */
    public function widget($expression)
    {
        return "<?php echo \$widget->render{$expression} ?>";
    }

    /**
     * messages
     *
     * @param   string $expression
     *
     * @return  string
     */
    public function messages($expression)
    {
        $expression = trim(static::stripParentheses($expression));

        $expression = $expression ? ', ' . $expression : '';

        return "<?php echo \\Windwalker\\Core\\Message\\MessageHelper::render(\$widget{$expression}) ?>";
    }

    /**
     * route
     *
     * @param   string $expression
     *
     * @return  string
     */
    public function route($expression)
    {
        return "<?php echo htmlspecialchars(\$router->generate{$expression}) ?>";
    }

    /**
     * css
     *
     * @param   string $expression
     *
     * @return  string
     */
    public function css($expression)
    {
        return "<?php \$asset->addCSS{$expression} ?>";
    }

    /**
     * js
     *
     * @param   string $expression
     *
     * @return  string
     */
    public function js($expression)
    {
        return "<?php \$asset->addJS{$expression} ?>";
    }

    /**
     * route
     *
     * @param   string $expression
     *
     * @return  string
     */
    public function assetTemplate($expression)
    {
        return "<?php \$asset->getTemplate()->startTemplate{$expression} ?>";
    }

    /**
     * route
     *
     * @param   string $expression
     *
     * @return  string
     */
    public function endTemplate($expression)
    {
        return "<?php \$asset->getTemplate()->endTemplate() ?>";
    }

    /**
     * auth
     *
     * @param   string $expression
     *
     * @return  string
     */
    public function auth($expression)
    {
        return "<?php if (\$app->user->authorise{$expression}): ?>";
    }

    /**
     * cannot
     *
     * @param   string $expression
     *
     * @return  string
     */
    public function cannot($expression)
    {
        return "<?php if (!\$app->user->authorise{$expression}): ?>";
    }

    /**
     * endauth
     *
     * @param   string $expression
     *
     * @return  string
     */
    public function endauth($expression)
    {
        return "<?php endif; ?>";
    }

    /**
     * formToken
     *
     * @param   string $expression
     *
     * @return  string
     */
    public function formToken($expression)
    {
        $expression = trim(static::stripParentheses($expression));

        return "<?php echo \$package->csrf->input({$expression}); ?>";
    }

    /**
     * show
     *
     * @param string $expression
     *
     * @return  string
     */
    public function shown($expression)
    {
        return "<?php show{$expression} ?>";
    }

    /**
     * dead
     *
     * @param string $expression
     *
     * @return  string
     */
    public function dd($expression)
    {
        return "<?php show{$expression}; die; ?>";
    }

    /**
     * dead
     *
     * @param string $expression
     *
     * @return  string
     */
    public function dead($expression)
    {
        return "<?php die{$expression} ?>";
    }

    /**
     * dead
     *
     * @param string $expression
     *
     * @return  string
     */
    public function debug($expression)
    {
        if ($expression) {
            $expression = static::stripParentheses($expression);

            return "<?php if(\$app->get('system.debug')) { {$expression}; } ?>";
        }

        return "<?php if(\$app->get('system.debug')): ?>";
    }

    /**
     * Strip the parentheses from the given expression.
     *
     * @param  string $expression
     *
     * @return string
     */
    public static function stripParentheses($expression)
    {
        if (strpos($expression, '(') === 0) {
            $expression = substr($expression, 1, -1);
        }

        return $expression;
    }
}
