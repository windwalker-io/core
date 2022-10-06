<?php

/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

declare(strict_types=1);

namespace Windwalker\Core\Error;

use Exception;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Throwable;
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
     * @var LoggerService
     */
    protected LoggerService $logger;

    /**
     * @var Config
     */
    protected Config $config;

    /**
     * ErrorLogHandler constructor.
     *
     * @param  LoggerService  $logger
     * @param  Config         $config
     * @param  array          $options
     */
    public function __construct(LoggerService $logger, Config $config, array $options = [])
    {
        $this->logger = $logger;
        $this->config = $config;

        $this->resolveOptions($options, [$this, 'configureOptions']);
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'enabled' => false,
                'channel' => 'error',
            ]
        );
    }

    /**
     * __invoke
     *
     * @param  Throwable  $e
     *
     * @return  void
     * @throws Exception
     */
    public function __invoke(Throwable $e): void
    {
        // Do not log 4xx errors
        $code = $e->getCode();

        if ($code < 400 || $code >= 500) {
            $message = static::handleExceptionLogText($e, $this->config->get('@root'));

            $this->logger->error(
                $this->config->getDeep('error.log_channel') ?? 'error',
                $message,
                ['exception' => $e]
            );
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
