<?php

declare(strict_types=1);

namespace Windwalker\Core\Crypt\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Utilities\Base64Url;
use Windwalker\Crypt\SecretToolkit;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Filesystem\Path;

use const Windwalker\Crypt\ENCODERS;

/**
 * The CryptKeyCommand class.
 */
#[CommandWrapper(
    description: 'Generate random key.'
)]
class CryptSecretCommand implements CommandInterface
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
            'How to encode this key. Allow list: ' . implode('|', ENCODERS),
            'base64url'
        );

        $command->addOption(
            'prefix',
            'p',
            InputOption::VALUE_OPTIONAL,
            'The key prefix'
        );

        $command->addOption(
            'replace',
            'r',
            InputOption::VALUE_OPTIONAL,
            'Replace APP_SECRET env variable.',
            false,
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
        $replace = $io->getOption('replace');

        $encode = $io->getOption('encode');

        $secret = SecretToolkit::genSecretString((int) $length, $encode);

        $prefix = $io->getOption('prefix');
        $secret = $prefix . $secret;

        $output = $io->getOption('output');

        if ($output) {
            $output = Path::realpath($output);
            Filesystem::mkdir(dirname($output));

            Filesystem::write($output, $secret);
        } else {
            $io->writeln($secret);
        }

        if ($replace !== false) {
            $replace ??= '.env';
            $replace = Path::realpath($replace);

            if (is_file($replace)) {
                $content = file_get_contents($replace);
                $content = preg_replace('/^APP_SECRET=.*$/m', 'APP_SECRET=' . $secret, $content);
                file_put_contents($replace, $content);

                $io->writeln('Replace APP_SECRET in file: ' . $replace);
            } else {
                $io->error('Replace file not exists: ' . $replace);
            }
        }

        return 0;
    }
}
