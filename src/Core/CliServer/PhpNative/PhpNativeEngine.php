<?php

/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\CliServer\PhpNative;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Windwalker\Core\CliServer\Contracts\CliServerEngineInterface;
use Windwalker\Core\Console\ConsoleApplication;
use Windwalker\Utilities\Options\OptionsResolverTrait;

/**
 * The PhpCliServer class.
 *
 * @psalm-type ServerOptions = array{
 *     main: string,
 *     docroot: string,
 * }
 */
class PhpNativeEngine implements CliServerEngineInterface
{
    use OptionsResolverTrait;

    public function __construct(
        protected string $name,
        protected ConsoleApplication $app,
        protected ConsoleOutputInterface $output,
        array $options = []
    ) {
        $this->resolveOptions($options, $this->configureOptions(...));
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->define('main')
            ->allowedTypes('string', 'null');

        $resolver->define('docroot')
            ->allowedTypes('string', 'null');

        $resolver->setDefined(['state_file']);
    }

    public static function isSupported(): bool
    {
        return true;
    }

    public function run(string $host, int $port, array $options = []): int
    {
        $host .= ':' . $port;

        $args = [];

        /** @var ServerOptions $options */
        $options = $this->getOptionsResolver()->resolve(
            array_merge(
                $this->options,
                $options
            )
        );

        $mainFile = $options['main'];

        if ($mainFile) {
            $args[] = $mainFile;
        } else {
            $root = $options['docroot'] ?: $this->app->path('@root') . '/www';
            $args[] = '-t';
            $args[] = $root;
        }

        $args = implode(' ', $args);

        $output = $this->getStyledOutput();

        $output->title('Windwalker Dev Server');
        $output->writeln('Starting...');
        $output->newLine();
        $output->writeln('Index: http://' . $host);
        $output->writeln('Dev: http://' . $host . '/dev.php');
        $output->newLine(2);

        $this->app->runProcess(
            "php -S $host $args",
            '',
            function ($type, $buffer) use ($output) {
                if (str_contains($buffer, '[404]')) {
                    $buffer = "<fg=yellow>$buffer</>";
                }

                if (str_contains($buffer, '[500]')) {
                    $buffer = "<fg=red>$buffer</>";
                }

                $output->write($buffer);
            }
        );

        return Command::SUCCESS;
    }

    /**
     * @return  SymfonyStyle
     */
    protected function getStyledOutput(): SymfonyStyle
    {
        return new SymfonyStyle(
            new ArrayInput([]),
            $this->output
        );
    }

    public function isRunning(): bool
    {
        return false;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
