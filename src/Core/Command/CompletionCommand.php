<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Command;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Filesystem\FileObject;

use function Windwalker\str;
use function Windwalker\uid;

/**
 * The CompletionCommand class.
 */
#[CommandWrapper(
    description: 'Create completion script.'
)]
class CompletionCommand implements CommandInterface
{
    protected IOInterface $io;

    public function configure(Command $command): void
    {
        // $command->addArgument(
        //     'task',
        //     InputArgument::OPTIONAL,
        //     'Task to run.',
        //     'register'
        // );
        $command->addOption(
            'shell-type',
            's',
            InputOption::VALUE_REQUIRED,
            'The shell type.'
        );
        $command->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Force override.'
        );
    }

    public function execute(IOInterface $io): int
    {
        $this->io = $io;

        $shell = $this->io->getOption('shell-type') ?? $this->getShellType();

        return $this->register($shell);
    }

    protected function register(string $shell): int
    {
        $home = $_SERVER['HOME'] ?? '~';
        $hookName = '.windwalker_completion_' . $shell;
        $hookFile = $home . '/' . $hookName;
        $force = $this->io->getOption('force');

        if (!is_file($hookFile) || $force) {
            $this->io->writeln('Write hook file to: ' . $hookFile);
            file_put_contents($hookFile, $this->hook($shell));
        } else {
            $this->io->writeln('File: <info>' . $hookFile . '</info> has exists.');
        }

        $profileFile = match ($shell) {
            'zsh' => $home . '/.zshrc',
            'bash' => $this->getBashProfile($home),
            default => throw new RuntimeException('No support for <info>' . $shell . '</info> now.')
        };

        $file = new FileObject($profileFile);

        $content = $file->exists() ? $file->read() : str();

        if ($content->contains($hookName)) {
            $this->io->writeln('File: <info>' . $file->getPathname() . '</info> already registered');

            return 0;
        }

        $content = $content->append("\nsource ~/$hookName\n");
        $file->write((string) $content);

        $this->io->writeln(
            sprintf(
                'Register source <comment>~/%s</comment> to <info>%s</info>',
                $hookName,
                $profileFile
            )
        );

        $this->io->writeln("Please restart terminal or type `<info>source $profileFile</info>` to refresh.");

        return 0;
    }

    protected function getBashProfile(string $home): string
    {
        $path = $home . '/.bash_profile';

        if (is_file($path)) {
            return $path;
        }

        return $home . '/.bashrc';
    }

    protected function hook(string $shell): string
    {
        return str_replace(
            '{name}',
            'windwalker_completion_' . uid(),
            file_get_contents(__DIR__ . '/../../../resources/shell/autocomplete/' . $shell)
        );
    }

    protected function genZshHook(): string
    {
        return str_replace(
            '{name}',
            'windwalker_completion_' . uid(),
            file_get_contents(__DIR__ . '/../../../resources/shell/autocomplete/zsh')
        );
    }

    /**
     * Determine the shell type for use with HookFactory
     *
     * @return string
     */
    protected function getShellType(): string
    {
        if (!getenv('SHELL')) {
            throw new RuntimeException(
                'Could not read SHELL environment variable. ' .
                'Please specify your shell type using the --shell-type option.'
            );
        }

        return basename(getenv('SHELL'));
    }
}
