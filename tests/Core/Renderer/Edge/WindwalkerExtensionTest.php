<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Renderer\Edge;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Asset\AssetService;
use Windwalker\Core\DateTime\ChronosService;
use Windwalker\Core\Language\LangService;
use Windwalker\Core\Renderer\Edge\WindwalkerExtension;
use Windwalker\Core\Renderer\RendererService;
use Windwalker\Core\Router\NavOptions;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Router\SystemUri;

#[CoversClass(WindwalkerExtension::class)]
class WindwalkerExtensionTest extends TestCase
{
    #[Test]
    public function langReturnsPhpEchoLangExpression(): void
    {
        $extension = new WindwalkerExtension($this->createStub(ApplicationInterface::class));

        self::assertSame(
            "<?php echo \$lang('hello') ?>",
            $extension->lang("('hello')")
        );
    }

    #[Test]
    public function choiceReturnsPhpEchoLangChoiceExpression(): void
    {
        $extension = new WindwalkerExtension($this->createStub(ApplicationInterface::class));

        self::assertSame(
            "<?php echo \$lang->choice('apple', 2) ?>",
            $extension->choice("('apple', 2)")
        );
    }

    #[Test]
    public function messagesReturnsPhpEchoEdgeRenderMessages(): void
    {
        $extension = new WindwalkerExtension($this->createStub(ApplicationInterface::class));

        self::assertSame(
            "<?php echo \$__edge->render('@messages') ?>",
            $extension->messages('')
        );
    }

    #[Test]
    public function teleportReturnsPhpAssetStartTeleportExpression(): void
    {
        $extension = new WindwalkerExtension($this->createStub(ApplicationInterface::class));

        self::assertSame(
            "<?php \$asset->startTeleport('foo') ?>",
            $extension->teleport("('foo')")
        );
    }

    #[Test]
    public function endTeleportStripsParenthesesAndWrapsEndTeleportCall(): void
    {
        $extension = new WindwalkerExtension($this->createStub(ApplicationInterface::class));

        self::assertSame(
            "<?php \$asset->endTeleport('foo') ?>",
            $extension->endTeleport("('foo')")
        );
    }

    #[Test]
    public function formTokenReturnsPhpEchoEdgeRenderCsrf(): void
    {
        $extension = new WindwalkerExtension($this->createStub(ApplicationInterface::class));

        self::assertSame(
            "<?php echo \$__edge->render('@csrf'); ?>",
            $extension->formToken()
        );
    }

    #[Test]
    public function cspNonceReturnsPhpEchoCspNonceServiceAttr(): void
    {
        $extension = new WindwalkerExtension($this->createStub(ApplicationInterface::class));

        self::assertSame(
            '<?php echo $app->service(\Windwalker\Core\Security\CspNonceService::class)?->attr(); ?>',
            $extension->cspNonce()
        );
    }

    #[Test]
    public function attrReturnsPhpEchoEdgeHelperAttrExpression(): void
    {
        $extension = new WindwalkerExtension($this->createStub(ApplicationInterface::class));

        self::assertSame(
            "<?php echo \Windwalker\Core\Renderer\Edge\EdgeHelper::attr('class', 'btn'); ?>",
            $extension->attr("('class', 'btn')")
        );
    }

    #[Test]
    public function shownReturnsPhpShowExpression(): void
    {
        $extension = new WindwalkerExtension($this->createStub(ApplicationInterface::class));

        self::assertSame(
            "<?php show(\$foo) ?>",
            $extension->shown('($foo)')
        );
    }

    #[Test]
    public function dumpReturnsPhpDumpExpression(): void
    {
        $extension = new WindwalkerExtension($this->createStub(ApplicationInterface::class));

        self::assertSame(
            "<?php dump(\$foo) ?>",
            $extension->dump('($foo)')
        );
    }

    #[Test]
    public function ddReturnsPhpShowExpressionFollowedByDie(): void
    {
        $extension = new WindwalkerExtension($this->createStub(ApplicationInterface::class));

        self::assertSame(
            "<?php show(\$foo); die; ?>",
            $extension->dd('($foo)')
        );
    }

    #[Test]
    public function dsReturnsPhpDsExpression(): void
    {
        $extension = new WindwalkerExtension($this->createStub(ApplicationInterface::class));

        self::assertSame(
            '<?php \Windwalker\ds($foo); ?>',
            $extension->ds('($foo)')
        );
    }

    #[Test]
    public function deadReturnsPhpDieExpression(): void
    {
        $extension = new WindwalkerExtension($this->createStub(ApplicationInterface::class));

        self::assertSame(
            '<?php die() ?>',
            $extension->dead('()')
        );
    }

    #[Test]
    public function debugWithExpressionWrapsItInIfDebugBlock(): void
    {
        $extension = new WindwalkerExtension($this->createStub(ApplicationInterface::class));

        self::assertSame(
            '<?php if($app->isDebug()) { $foo = 1; } ?>',
            $extension->debug('($foo = 1)')
        );
    }

    #[Test]
    public function debugWithoutExpressionReturnsIfDebugAlternativeSyntax(): void
    {
        $extension = new WindwalkerExtension($this->createStub(ApplicationInterface::class));

        self::assertSame(
            '<?php if($app->isDebug()): ?>',
            $extension->debug('')
        );
    }

    #[Test]
    public function enddebugReturnsEndifTag(): void
    {
        $extension = new WindwalkerExtension($this->createStub(ApplicationInterface::class));

        self::assertSame('<?php endif; ?>', $extension->enddebug(''));
    }

    #[Test]
    public function canReturnsIfAuthServiceCanExpression(): void
    {
        $extension = new WindwalkerExtension($this->createStub(ApplicationInterface::class));

        self::assertSame(
            '<?php if ($app->service(\Windwalker\Core\Auth\AuthService::class)->can(\'edit\', $post)): ?>',
            $extension->can("('edit', \$post)")
        );
    }

    #[Test]
    public function cannotReturnsIfAuthServiceCannotExpression(): void
    {
        $extension = new WindwalkerExtension($this->createStub(ApplicationInterface::class));

        self::assertSame(
            '<?php if ($app->service(\Windwalker\Core\Auth\AuthService::class)->cannot(\'edit\', $post)): ?>',
            $extension->cannot("('edit', \$post)")
        );
    }

    #[Test]
    public function endcanReturnsEndifTag(): void
    {
        $extension = new WindwalkerExtension($this->createStub(ApplicationInterface::class));

        self::assertSame('<?php endif; ?>', $extension->endcan(''));
    }

    #[Test]
    public function stripParenthesesRemovesSurroundingParens(): void
    {
        self::assertSame('$foo = 1', WindwalkerExtension::stripParentheses('($foo = 1)'));
    }

    #[Test]
    public function stripParenthesesLeavesExpressionWithoutLeadingParenUnchanged(): void
    {
        self::assertSame('$foo = 1', WindwalkerExtension::stripParentheses('$foo = 1'));
    }

    #[Test]
    public function getGlobalsResolvesDependenciesFromAppAndCachesResult(): void
    {
        $uri = $this->createStub(SystemUri::class);
        $chronos = $this->createStub(ChronosService::class);
        $asset = $this->createStub(AssetService::class);
        $lang = $this->createStub(LangService::class);
        $navigator = $this->createMock(Navigator::class);
        $rendererService = $this->createMock(RendererService::class);

        $navigator->expects($this->once())
            ->method('withOptions')
            ->with(self::isInstanceOf(NavOptions::class))
            ->willReturn($navigator);

        $rendererService->expects($this->once())
            ->method('getGlobals')
            ->willReturn(['extra' => 'value']);

        $app = $this->createMock(ApplicationInterface::class);
        $app->method('isDebug')->willReturn(false);
        $app->method('resolve')
            ->willReturnMap(
                [
                    [SystemUri::class, $uri],
                    [ChronosService::class, $chronos],
                    [AssetService::class, $asset],
                    [LangService::class, $lang],
                    [Navigator::class, $navigator],
                ]
            );
        $app->expects($this->once())
            ->method('service')
            ->with(RendererService::class)
            ->willReturn($rendererService);

        $extension = new WindwalkerExtension($app);

        $globals = $extension->getGlobals();

        self::assertSame($app, $globals['app']);
        self::assertSame($uri, $globals['uri']);
        self::assertSame($chronos, $globals['chronos']);
        self::assertSame($asset, $globals['asset']);
        self::assertSame($lang, $globals['lang']);
        self::assertSame($navigator, $globals['nav']);
        self::assertSame('value', $globals['extra']);

        // Calling again must hit the instance cache instead of re-resolving.
        $extension->getGlobals();
    }

    #[Test]
    public function getGlobalsEnablesNavDebugAlertWhenAppIsInDebugMode(): void
    {
        $navigator = $this->createStub(Navigator::class);
        $capturedOptions = null;

        $navigator->method('withOptions')
            ->willReturnCallback(
                function (NavOptions $options) use ($navigator, &$capturedOptions) {
                    $capturedOptions = $options;

                    return $navigator;
                }
            );

        $rendererService = $this->createStub(RendererService::class);
        $rendererService->method('getGlobals')->willReturn([]);

        $app = $this->createStub(ApplicationInterface::class);
        $app->method('isDebug')->willReturn(true);
        $app->method('resolve')
            ->willReturnCallback(
                fn(string $class) => match ($class) {
                    Navigator::class => $navigator,
                    default => $this->createStub($class),
                }
            );
        $app->method('service')->willReturn($rendererService);

        $extension = new WindwalkerExtension($app);
        $extension->getGlobals();

        self::assertTrue($capturedOptions->debugAlert);
        self::assertTrue($capturedOptions->mute);
    }

    #[Test]
    public function handleInPageAssetsRemovesStyleBlocksWithDataMacroAttribute(): void
    {
        $extension = new WindwalkerExtension($this->createStub(ApplicationInterface::class));
        $handleInPageAssets = $extension->getParsers()[0];

        $content = '<div>Before</div><style data-macro id="s1">body{color:red}</style><div>After</div>';

        self::assertSame(
            '<div>Before</div><div>After</div>',
            $handleInPageAssets($content)
        );
    }

    #[Test]
    public function handleInPageAssetsLeavesStyleBlocksWithoutDataMacroUnchanged(): void
    {
        $extension = new WindwalkerExtension($this->createStub(ApplicationInterface::class));
        $handleInPageAssets = $extension->getParsers()[0];

        $content = '<style id="s1">body{color:red}</style>';

        self::assertSame($content, $handleInPageAssets($content));
    }
}
