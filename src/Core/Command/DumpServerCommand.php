<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Command\Descriptor\CliDescriptor;
use Symfony\Component\VarDumper\Command\Descriptor\HtmlDescriptor;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\Server\DumpServer;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Console\ConsoleApplication;

/**
 * The DumpServerCommand class.
 */
#[CommandWrapper(
    description: 'Start the dump server to collect dump information.'
)]
class DumpServerCommand implements CommandInterface
{
    /**
     * DumpServerCommand constructor.
     *
     * @param  ConsoleApplication  $app
     */
    public function __construct(protected ConsoleApplication $app)
    {
    }

    /**
     * @inheritDoc
     */
    public function configure(Command $command): void
    {
        $command->addArgument(
            'host',
            InputArgument::OPTIONAL,
            'Server listening Host.'
        );
        $command->addOption(
            'format',
            'f',
            InputOption::VALUE_OPTIONAL,
            'The output format (cli,html)',
            'cli'
        );
    }

    /**
     * @inheritDoc
     */
    public function execute(IOInterface $io): int
    {
        if (!class_exists(DumpServer::class)) {
            throw new \DomainException('Please install symfony/var-dumper ^5.0 first.');
        }

        $descriptor = match ($format = $io->getOption('format')) {
            'cli' => new CliDescriptor(new CliDumper()),
            'html' => new HtmlDescriptor(new HtmlDumper()),
            default => throw new InvalidOptionException("Unsupported format: $format")
        };

        $host = $io->getArgument('host')
            ?? $this->app->config('app.dump_server.host')
            ?? 'tcp://127.0.0.1:9912';

        $server = new DumpServer($host);

        $errIO = $io->errorStyle();
        $errIO->title('Windwalker Dump Server');

        $server->start();

        $errIO->success(sprintf('Server listening on %s', $server->getHost()));
        $errIO->note('Quit the server with CONTROL-C.');

        $server->listen(function (Data $data, array $context, int $clientId) use ($descriptor, $io) {
            $descriptor->describe($io, $data, $context, $clientId);

            $io->style()->newLine(3);
        });

        return 0;
    }
}
