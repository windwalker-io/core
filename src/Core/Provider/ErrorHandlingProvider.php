<?php

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use NunoMaduro\Collision\Writer;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Whoops\Exception\Inspector;
use Windwalker\Core\Application\AppClient;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Console\Collision\SolutionRepository;
use Windwalker\Core\Runtime\Config;
use Windwalker\Core\Service\ErrorService;
use Windwalker\DI\BootableProviderInterface;
use Windwalker\DI\Container;
use Windwalker\DI\DIOptions;
use Windwalker\DI\ServiceProviderInterface;

/**
 * The ErrorHandleringProvider class.
 *
 * @since  4.0
 */
class ErrorHandlingProvider implements ServiceProviderInterface, BootableProviderInterface
{
    use IniSetterTrait;

    /**
     * @var Config
     */
    protected Config $config;

    /**
     * ErrorHandlingProvider constructor.
     *
     * @param  Config  $config
     * @param  ApplicationInterface  $app
     */
    public function __construct(Config $config, protected ApplicationInterface $app)
    {
        $this->config = $config->extract('error');
    }

    /**
     * @inheritDoc
     */
    public function boot(Container $container): void
    {
        if (!$this->app->getType()->isCliWeb()) {
            $iniValues = $this->config->get('ini') ?? [];

            static::setINIValues($iniValues, $container);
        }

        $error = $container->get(ErrorService::class);

        switch ($this->app->getClient()) {
            case AppClient::WEB:
            default:
                // Runtime CLI do not restore exception handler, let console app handle it.
                if (!$this->app->isCliRuntime()) {
                    ini_set('display_errors', 'stderr');

                    $error->register(
                        (bool) ($this->config->get('restore') ?? true),
                        (int) ($this->config->get('report_level') ?? E_ALL),
                        (bool) ($this->config->get('register_shutdown') ?? true)
                    );
                }
                break;

            case AppClient::CONSOLE:
                $this->app->on(ConsoleErrorEvent::class, function (ConsoleErrorEvent $event) use ($error) {
                    $t = $event->getError();

                    if (class_exists(Writer::class)) {
                        $writer = new Writer(
                            new SolutionRepository(),
                            $event->getOutput()
                        );
                        $writer->write(new Inspector($t));
                    }

                    $error->handle($t);
                });

                // To hide default uncaught errors and backtraces.
                $error->register(false, E_ALL, true);
                break;
        }
    }

    /**
     * @inheritDoc
     */
    public function register(Container $container): void
    {
        $container->prepareSharedObject(
            ErrorService::class,
            function (ErrorService $error, Container $container) {
                // Error Handlers
                $appType = $this->app->getType()->name;

                foreach ($this->config->getDeep('handlers.' . $appType) ?? [] as $key => $handler) {
                    $error->addHandler($container->resolve($handler), is_numeric($key) ? null : $key);
                }

                // Error Handlers all
                foreach ($this->config->getDeep('handlers.all') ?? [] as $key => $handler) {
                    $error->addHandler($container->resolve($handler), is_numeric($key) ? null : $key);
                }

                // Deprecation handlers.
                foreach ($this->config->getDeep('deprecation_handlers.' . $appType) ?? [] as $key => $handler) {
                    $error->addDeprecationHandler($container->resolve($handler), is_numeric($key) ? null : $key);
                }

                // Deprecation handlers all.
                foreach ($this->config->getDeep('deprecation_handlers.all') ?? [] as $key => $handler) {
                    $error->addDeprecationHandler($container->resolve($handler), is_numeric($key) ? null : $key);
                }

                return $error;
            },
            new DIOptions(isolation: true)
        );
    }
}
