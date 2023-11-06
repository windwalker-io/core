<?php

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Application\Context\AppContextInterface;
use Windwalker\Core\Application\Context\AppRequestInterface;
use Windwalker\Core\Console\ConsoleApplication;
use Windwalker\Core\Http\AppRequest;
use Windwalker\Core\Http\Browser;
use Windwalker\Core\Http\ProxyResolver;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Router\SystemUri;
use Windwalker\Core\Security\CspNonceService;
use Windwalker\Core\State\AppState;
use Windwalker\DI\Container;
use Windwalker\DI\Exception\DefinitionException;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Http\Request\ServerRequest;

/**
 * The ConsoleProvider class.
 */
class ConsoleProvider implements ServiceProviderInterface
{
    /**
     * ConsoleProvider constructor.
     *
     * @param  ConsoleApplication  $app
     */
    public function __construct(protected ConsoleApplication $app)
    {
    }

    /**
     * @inheritDoc
     */
    public function register(Container $container): void
    {
        if (ConsoleApplication::class !== $this->app::class) {
            $container->alias(ConsoleApplication::class, $this->app::class);
        }

        $container->mergeParameters(
            'commands',
            require __DIR__ . '/../../../resources/registry/commands.php'
        );

        $container->mergeParameters(
            'generator.commands',
            require __DIR__ . '/../../../resources/registry/generator.php'
        );

        $this->registerEmptyWebServices($container);
    }

    /**
     * Prepare some empty web services to make sure other services which depends on these will not error in CLI mode.
     *
     * If you call $app->prepareWebSimulator(), they will be override.
     *
     * @param  Container  $container
     *
     * @return  void
     *
     * @throws DefinitionException
     */
    protected function registerEmptyWebServices(Container $container): void
    {
        $container->prepareSharedObject(ServerRequest::class)
            ->alias(ServerRequestInterface::class, ServerRequest::class);

        $container->prepareSharedObject(SystemUri::class);
        $container->prepareSharedObject(ProxyResolver::class);
        $container->prepareSharedObject(Browser::class);
        $container->prepareSharedObject(CspNonceService::class);
        $container->prepareSharedObject(AppState::class);
        $container->prepareSharedObject(AppContext::class)
            ->alias(AppContextInterface::class, AppContext::class);

        $container->share(
            AppRequest::class,
            fn (Container $container) => $container->get(AppContext::class)->getAppRequest()
        )
            ->alias(AppRequestInterface::class, AppRequest::class);

        $container->prepareSharedObject(Navigator::class);
    }
}
