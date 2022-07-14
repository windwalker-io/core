<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Crypt\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Utilities\Base64Url;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Filesystem\Path;

/**
 * The CryptKeyCommand class.
 */
#[CommandWrapper(
    description: 'Generate random key.'
)]
class CryptKeyCommand implements CommandInterface
{
    /**
     * configure
     *
     * @param  Command  $command
     *
     * @return  void
     */
    public function configure(Command $command): void
    {
        $command->addArgument(
            'length',
            InputArgument::OPTIONAL,
            'The bytes length.',
            '32'
        );

        $command->addOption(
            'output',
            'o',
            InputOption::VALUE_REQUIRED,
            'The output file, keep empty will print to terminal.'
        );

        $command->addOption(
            'encode',
            'e',
            InputOption::VALUE_REQUIRED,
            'How to encode this key.',
            'base64url'
        );

        $command->addOption(
            'prefix',
            'p',
            InputOption::VALUE_OPTIONAL,
            'The key prefix'
        );
    }

    /**
     * Executes the current command.
     *
     * @param  IOInterface  $io
     *
     * @return  int Return 0 is success, 1-255 is failure.
     * @throws \Exception
     */
    public function execute(IOInterface $io): int
    {
        $length = $io->getArgument('length');

        $str = random_bytes((int) $length);

        $encode = $io->getOption('encode');

        $str = match ($encode) {
            default => Base64Url::encode($str),
            'base64' => base64_encode($str),
            'hex' => bin2hex($str),
        };

        $prefix = $io->getOption('prefix');
        $str = $prefix . $str;

        $output = $io->getOption('output');

        if ($output) {
            $output = Path::realpath($output);
            Filesystem::mkdir(dirname($output));

            Filesystem::write($output, $str);
        } else {
            $io->writeln($str);
        }

        return 0;
    }
}
