<?php

declare(strict_types=1);

namespace Windwalker\Core\Renderer\Edge;

use Dom\Element;
use Dom\HTMLDocument;
use Dom\HTMLElement;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Asset\AssetService;
use Windwalker\Core\DateTime\ChronosService;
use Windwalker\Core\Language\LangService;
use Windwalker\Core\Renderer\RendererService;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Router\RouteUri;
use Windwalker\Core\Router\SystemUri;
use Windwalker\DOM\HTML5Factory;
use Windwalker\Edge\Extension\DirectivesExtensionInterface;
use Windwalker\Edge\Extension\EdgeExtensionInterface;
use Windwalker\Edge\Extension\GlobalVariablesExtensionInterface;
use Windwalker\Edge\Extension\ParsersExtensionInterface;
use Windwalker\Utilities\Cache\InstanceCacheTrait;
use Windwalker\Utilities\Str;

/**
 * The WindwalkerExtension class.
 *
 * @since  3.0
 */
class WindwalkerExtension implements
    EdgeExtensionInterface,
    DirectivesExtensionInterface,
    GlobalVariablesExtensionInterface,
    ParsersExtensionInterface
{
    use InstanceCacheTrait;

    /**
     * WindwalkerExtension constructor.
     *
     * @param  ApplicationInterface  $app
     */
    public function __construct(protected ApplicationInterface $app)
    {
    }

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
            'lang' => $this->lang(...),
            'translate' => $this->lang(...),
            'choice' => $this->choice(...),
            'messages' => $this->messages(...),
            // 'widget' => $this->widget(...),
            // 'route' => $this->route(...),
            'formToken' => $this->formToken(...),
            'csrf' => $this->formToken(...),
            'nonce' => $this->cspNonce(...),

            // Authorisation
            'can' => $this->can(...),
            'cannot' => $this->cannot(...),
            'endcan' => $this->endcan(...),
            'endcannot' => $this->endcan(...),

            // Asset
            // 'css' => $this->css(...),
            // 'js' => $this->js(...),
            'teleport' => $this->teleport(...),
            'endTeleport' => $this->endTeleport(...),
            'attr' => $this->attr(...),

            // Debug
            'dump' => $this->dump(...),
            'shown' => $this->shown(...),
            'dd' => $this->dd(...),
            'ds' => $this->ds(...),
            'die' => $this->dead(...),
            'debug' => $this->debug(...),
            'enddebug' => $this->enddebug(...),
        ];
    }

    /**
     * translate
     *
     * @param  string  $expression
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
     * @param  string  $expression
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
     * @param  string  $expression
     *
     * @return  string
     */
    public function messages(string $expression): string
    {
        return "<?php echo \$__edge->render('@messages') ?>";
    }

    /**
     * route
     *
     * @param  string  $expression
     *
     * @return  string
     */
    public function teleport(string $expression): string
    {
        return "<?php \$asset->startTeleport{$expression} ?>";
    }

    /**
     * route
     *
     * @param  string  $expression
     *
     * @return  string
     */
    public function endTeleport(string $expression): string
    {
        $expression = static::stripParentheses($expression);

        return "<?php \$asset->endTeleport($expression) ?>";
    }

    /**
     * formToken
     *
     * @return  string
     */
    public function formToken(): string
    {
        return "<?php echo \$__edge->render('@csrf'); ?>";
    }

    /**
     * formToken
     *
     * @return  string
     */
    public function cspNonce(): string
    {
        return "<?php echo \$app->service(\Windwalker\Core\Security\CspNonceService::class)?->attr(); ?>";
    }

    /**
     * attr
     *
     * @param  string  $expression
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
     * @param  string  $expression
     *
     * @return  string
     *
     * @since  3.3
     */
    public function shown(string $expression): string
    {
        return "<?php show{$expression} ?>";
    }

    public function dump(string $expression): string
    {
        return "<?php dump{$expression} ?>";
    }

    /**
     * dead
     *
     * @param  string  $expression
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
     * @param  string  $expression
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
     * @param  string  $expression
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
     * @param  string  $expression
     *
     * @return  string
     *
     * @since  3.3
     */
    public function debug(string $expression): string
    {
        $expression = static::stripParentheses($expression);

        if ($expression) {
            return "<?php if(\$app->isDebug()) { {$expression}; } ?>";
        }

        return "<?php if(\$app->isDebug()): ?>";
    }

    /**
     * enddebug
     *
     * @param  string  $expression
     *
     * @return  string
     */
    public function enddebug(string $expression): string
    {
        return "<?php endif; ?>";
    }

    public function can(string $expression): string
    {
        return "<?php if (\$app->service(\Windwalker\Core\Auth\AuthService::class)->can{$expression}): ?>";
    }

    public function cannot(string $expression): string
    {
        return "<?php if (\$app->service(\Windwalker\Core\Auth\AuthService::class)->cannot{$expression}): ?>";
    }

    public function endcan(string $expression): string
    {
        return '<?php endif; ?>';
    }

    /**
     * Strip the parentheses from the given expression.
     *
     * @param  string  $expression
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

    /**
     * getGlobals
     *
     * @return  array
     */
    public function getGlobals(): array
    {
        return $this->once(
            'globals',
            function () {
                $globals = [];

                $globals['app'] = $this->app;
                $globals['uri'] = $this->app->resolve(SystemUri::class);
                $globals['chronos'] = $this->app->resolve(ChronosService::class);
                $globals['asset'] = $this->app->resolve(AssetService::class);
                // $globals['theme'] = $this->app->resolve(ThemeInterface::class);
                $globals['lang'] = $this->app->resolve(LangService::class);

                $navOptions = RouteUri::MODE_MUTE;

                if ($this->app->isDebug()) {
                    $navOptions |= RouteUri::DEBUG_ALERT;
                }

                $globals['nav'] = $this->app->resolve(Navigator::class)
                    ->withOptions($navOptions);

                return array_merge(
                    $globals,
                    $this->app->service(RendererService::class)->getGlobals()
                );
            }
        );
    }

    /**
     * @inheritDoc
     */
    public function getParsers(): array
    {
        return [
            $this->handleInPageAssets(...),
            $this->inlineTsToLoader(...),
        ];
    }

    protected function handleInPageAssets(string $content): string
    {
        $regexes = [
            // Remove <style data-macro>...</style>
            '/<style\b[^>]*\bdata-macro[^>]*>.*?<\/style>/is',
        ];

        return preg_replace(
            $regexes,
            '',
            $content
        );
    }

    protected function inlineTsToLoader(string $content): string
    {
        $regex = "'<script[^>]*>.*?</script>'si";

        return preg_replace_callback(
            $regex,
            static function ($matches) {
                $element = HTML5Factory::parse($matches[0]);

                if (!$id = $element->getAttribute('data-macro')) {
                    return $matches[0];
                }

                if ($element->getAttribute('lang') === 'ts' && trim($element->textContent)) {
                    $propString = '[';
                    foreach ($element->dataset->toArray() as $k => $v) {
                        if ($k === 'props') {
                            $propString .= "...$v,";
                            continue;
                        }

                        if (!str_starts_with($k, 'prop:')) {
                            continue;
                        }

                        $k = str_replace("'", "\'", Str::removeLeft($k, 'prop:'));
                        $propString .= "'$k' => $v,";
                    }
                    $propString .= ']';

                    return "<?php \$asset->importSyncByApp('inline:{$id}', $propString); ?>";
                }

                return '';
            },
            $content
        );
    }
}
