<?php

declare(strict_types=1);

namespace Windwalker\Core\Error;

use Exception;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Throwable;
use Windwalker\Core\Application\AppVerbosity;
use Windwalker\Core\Runtime\Config;
use Windwalker\Core\Service\LoggerService;
use Windwalker\Utilities\Options\OptionsResolverTrait;
use Windwalker\Utilities\Reflection\BacktraceHelper;

/**
 * The ErrorLogHandler class.
 *
 * @since  3.0
 */
class ErrorLogHandler implements ErrorHandlerInterface
{
    use OptionsResolverTrait;

    /**
     * ErrorLogHandler constructor.
     *
     * @param  LoggerService  $logger
     * @param  Config         $config
     * @param  AppVerbosity   $verbosity
     * @param  array          $options
     */
    public function __construct(
        protected LoggerService $logger,
        protected Config $config,
        protected AppVerbosity $verbosity,
        array $options = []
    ) {
        $this->resolveOptions($options, [$this, 'configureOptions']);
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'enabled' => false,
                'channel' => $this->config->getDeep('error.log_channel') ?: 'error',
                'ignore_40x' => true,
                'print' => false,
                'backtraces' => true,
            ]
        );
    }

    /**
     * @param  Throwable  $e
     *
     * @return  void
     * @throws Exception
     */
    public function __invoke(Throwable $e): void
    {
        if (!$this->options['enabled']) {
            return;
        }

        // Do not log 4xx errors
        $code = $e->getCode();

        $ignore40x = (bool) $this->options['ignore_40x'];

        if (
            $code < 400 || $code >= 500 || !$ignore40x
        ) {
            $message = $this->verbosity->debugMessage($e);

            if ($this->options['channel']) {
                $context = [];

                if ($this->options['backtraces']) {
                    $context['exception'] = $e;
                }

                $this->logger->error(
                    $this->options['channel'],
                    $message,
                    $context
                );
            }

            if ($this->options['print']) {
                echo $message;
            }
        }
    }

    /**
     * @param  Throwable  $e
     * @param  string     $root
     *
     * @return  string
     */
    public static function handleExceptionLogText(Throwable $e, string $root): string
    {
        $message = sprintf(
            'Code: %s - %s - File: %s (%d)',
            $e->getCode(),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );

        $traces = '';

        foreach (
            BacktraceHelper::normalizeBacktraces($e->getTrace(), $root) as $i => $trace
        ) {
            $traces .= '    #' . ($i + 1) . ' - ' . $trace['function'] . ' ' . $trace['file'] . "\n";
        }

        return $message . "\n" . $traces;
    }
}
