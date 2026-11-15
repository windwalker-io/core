<?php

declare(strict_types=1);

namespace Windwalker\Core\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Application\ApplicationInterface;

#[CommandWrapper(
    description: 'Start a REPL console or exec code.',
)]
class ReplExecCommand implements CommandInterface
{
    protected IOInterface $io;

    public function __construct(protected ApplicationInterface $app) {}

    public function configure(Command $command): void
    {
        $command->addOption(
            'code',
            'c',
            InputOption::VALUE_REQUIRED,
            'Code to execute.'
        );

        $command->addArgument(
            'file',
            InputArgument::OPTIONAL,
            'File to execute.',
        );
    }

    public function execute(IOInterface $io): int
    {
        $this->io = $io;

        // Arbitrary code execution is only allowed on a local CLI in debug mode.
        if (!$this->app->isCliRuntime() || !$this->app->isDebug()) {
            throw new \RuntimeException(
                'The repl command is only available in CLI runtime with debug mode enabled.'
            );
        }

        $code = $io->getOption('code');
        $file = $io->getArgument('file');

        if ($code !== null && $code !== '' && $file !== null && $file !== '') {
            throw new \InvalidArgumentException('Cannot use --code and file argument at the same time.');
        }

        if ($file !== null && $file !== '') {
            return $this->runFile($io, $file);
        }

        if ($code !== null && $code !== '') {
            return $this->runCode($io, $code);
        }

        if (!$io->getInput()->isInteractive()) {
            throw new \RuntimeException('Interactive REPL requires an interactive terminal.');
        }

        $shell = new \Psy\Shell();
        $shell->setScopeVariables($this->getVars());
        $shell->run();

        return Command::SUCCESS;
    }

    protected function runFile(IOInterface $io, string $file): int
    {
        $root = realpath($this->app->path('@root'));
        $real = realpath($file);

        if ($root === false || $real === false || !is_file($real)) {
            throw new \RuntimeException(sprintf('File "%s" not found.', $file));
        }

        if (!str_starts_with($real, $root . DIRECTORY_SEPARATOR) || !str_ends_with($real, '.php')) {
            throw new \RuntimeException('Only .php files inside the project root can be executed.');
        }

        return $this->executeIsolated(
            static function (array $__vars, string $__file) {
                extract($__vars, EXTR_SKIP);

                return include $__file;
            },
            $io,
            $real
        );
    }

    protected function runCode(IOInterface $io, string $code): int
    {
        // Compile first so a syntax error never causes partial execution.
        // Try as an expression, otherwise as statements; code is never run twice.
        $code = rtrim(trim($code), ';');

        try {
            token_get_all('<?php return ' . $code . ';', TOKEN_PARSE);
            $source = 'return ' . $code . ';';
        } catch (\ParseError) {
            $source = $code . ';';
        }

        return $this->executeIsolated(
            static function (array $__vars, string $__source) {
                extract($__vars, EXTR_SKIP);

                return eval($__source);
            },
            $io,
            $source
        );
    }

    protected function executeIsolated(\Closure $runner, IOInterface $io, string $target): int
    {
        $level = ob_get_level();
        ob_start();

        try {
            $result = $runner($this->getVars(), $target);
        } catch (\Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $e;
        }

        $stdout = '';

        while (ob_get_level() > $level) {
            $stdout = ob_get_clean() . $stdout;
        }

        if ($stdout !== '') {
            $io->writeln($stdout);
        }

        if ($result !== null && $result !== 1) {
            if (function_exists('dump')) {
                dump($result);
            } else {
                $io->writeln(var_export($result, true));
            }
        }

        return Command::SUCCESS;
    }

    protected function getVars(): array
    {
        return [
            'io' => $this->io,
            'app' => $this->app,
        ];
    }
}
