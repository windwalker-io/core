<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Renderer\Edge;

use Windwalker\Edge\Extension\DirectivesExtensionInterface;
use Windwalker\Edge\Extension\EdgeExtensionInterface;

/**
 * The WindwalkerExtension class.
 *
 * @since  3.0
 */
class WindwalkerExtension implements EdgeExtensionInterface, DirectivesExtensionInterface
{
    /**
     * getName
     *
     * @return  string
     */
    public function getName(): string
    {
        return 'windwalker';
    }

    /**
     * getDirectives
     *
     * @return  callable[]
     */
    public function getDirectives(): array
    {
        return [
            'lang' => [$this, 'translate'],
            'translate' => [$this, 'translate'],
            'choice' => [$this, 'choice'],
            'messages' => [$this, 'messages'],
            // 'widget' => [$this, 'widget'],
            // 'route' => [$this, 'route'],
            'formToken' => [$this, 'formToken'],

            // Authorisation
            // 'can' => [$this, 'can'],
            // 'cannot' => [$this, 'cannot'],
            // 'endcan' => [$this, 'endcan'],
            // 'endcannot' => [$this, 'endcan'],

            // Asset
            'css' => [$this, 'css'],
            'js' => [$this, 'js'],
            'teleport' => [$this, 'teleport'],
            'endTeleport' => [$this, 'endTeleport'],
            'attr' => [$this, 'attr'],

            // Debug
            'shown' => [$this, 'shown'],
            'dd' => [$this, 'dd'],
            'ds' => [$this, 'ds'],
            'die' => [$this, 'dead'],
            'debug' => [$this, 'debug'],
            'enddebug' => [$this, 'endauth'],
        ];
    }

    /**
     * translate
     *
     * @param   string $expression
     *
     * @return  string
     */
    public function lang(string $expression): string
    {
        return "<?php echo \$lang{$expression} ?>";
    }

    /**
     * sprintf
     *
     * @param   string $expression
     *
     * @return  string
     */
    public function choice(string $expression): string
    {
        return "<?php echo \$lang->choice{$expression} ?>";
    }

    /**
     * messages
     *
     * @param   string $expression
     *
     * @return  string
     */
    public function messages(string $expression): string
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
    public function teleport(string $expression): string
    {
        return "<?php \$asset->getTeleport()->start{$expression} ?>";
    }

    /**
     * route
     *
     * @param   string $expression
     *
     * @return  string
     */
    public function endTeleport(string $expression): string
    {
        return "<?php \$asset->getTeleport()->end() ?>";
    }

    /**
     * formToken
     *
     * @param   string $expression
     *
     * @return  string
     */
    public function formToken(string $expression): string
    {
        $expression = trim(static::stripParentheses($expression));

        return /** @lang php */ "<?php echo \$app->service(\Windwalker\Core\Security\CsrfService::class)->input({$expression}); ?>";
    }

    /**
     * attr
     *
     * @param string $expression
     *
     * @return  string
     *
     * @since  3.3
     */
    public function attr(string $expression): string
    {
        return "<?php echo \Windwalker\Core\Renderer\Edge\EdgeHelper::attr{$expression}; ?>";
    }

    /**
     * show
     *
     * @param string $expression
     *
     * @return  string
     *
     * @since  3.3
     */
    public function shown(string $expression): string
    {
        return "<?php show{$expression} ?>";
    }

    /**
     * dead
     *
     * @param string $expression
     *
     * @return  string
     *
     * @since   3.3
     */
    public function dd(string $expression): string
    {
        return "<?php show{$expression}; die; ?>";
    }

    /**
     * ds
     *
     * @param string $expression
     *
     * @return  string
     *
     * @since  3.4.8
     */
    public function ds(string $expression): string
    {
        return "<?php ds{$expression}; ?>";
    }

    /**
     * dead
     *
     * @param string $expression
     *
     * @return  string
     *
     * @since   3.3
     */
    public function dead(string $expression): string
    {
        return "<?php die{$expression} ?>";
    }

    /**
     * dead
     *
     * @param string $expression
     *
     * @return  string
     *
     * @since  3.3
     */
    public function debug(string $expression): string
    {
        if ($expression) {
            $expression = static::stripParentheses($expression);

            return "<?php if(\$app->isDebug()) { {$expression}; } ?>";
        }

        return "<?php if(\$app->isDebug()): ?>";
    }

    /**
     * enddebug
     *
     * @param   string $expression
     *
     * @return  string
     */
    public function enddebug(string $expression): string
    {
        return "<?php endif; ?>";
    }

    /**
     * Strip the parentheses from the given expression.
     *
     * @param  string $expression
     *
     * @return string
     */
    public static function stripParentheses(string $expression): string
    {
        if (str_starts_with($expression, '(')) {
            $expression = substr($expression, 1, -1);
        }

        return $expression;
    }
}
