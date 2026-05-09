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
use Windwalker\Filesystem\Path;

#[CommandWrapper(
    description: 'Start a REPL console or exec code.',
)]
class ReplCommand implements CommandInterface
{
    protected IOInterface $io;

    protected ?string $code = null;

    public function __construct(protected ApplicationInterface $app)
    {
    }

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
        $code = $io->getOption('code');
        $file = $io->getArgument('file');

        $this->io = $io;

        // todo: Reuse code for code mode and file mode

        // If --file/-f provided, execute file non-interactively.
        if ($file !== null && $file !== '') {
            $file = Path::realpath($file);

            if (!is_file($file)) {
                throw new \RuntimeException(sprintf('File "%s" not found.', $file));
            }

            try {
                ob_start();

                $vars = $this->getVars();
                extract($vars);

                // Use include so the file can return a value. It will run in this scope.
                $result = include $real;

                $stdout = ob_get_clean();

                if ($stdout !== '') {
                    $io->writeln($stdout);
                }

                if (isset($result) && $result !== null) {
                    dump($result);
                }

                return Command::SUCCESS;
            } catch (\Throwable $e) {
                ob_get_clean();

                throw $e;
            }
        }

        // If --code/-c option provided, execute the PHP code non-interactively.
        if ($code !== null && $code !== '') {
            ob_start();

            $this->code = $code;

            $runner = function () {
                $vars = $this->getVars();

                extract($vars);

                // Try evaluate as expression first, fallback to statement execution.
                try {
                    return eval('return ' . $this->getCode() . ';');
                } catch (\Throwable $e) {
                    ob_end_clean();
                    ob_start();
                    return eval($this->getCode() . ';');
                }
            };

            try {
                $result = $runner();
            } catch (\Throwable $e) {
                ob_end_clean();
                throw $e;
            }

            $stdout = ob_get_clean();

            if ($stdout !== '') {
                $io->writeln($stdout);
            }

            if (isset($result) && $result !== null) {
                if (function_exists('dump')) {
                    dump($result);
                } else {
                    $io->writeln(var_export($result, true));
                }
            }

            return Command::SUCCESS;
        }

        // Fallback to interactive REPL using PsySH
        // Instantiate PsySH shell and expose $io as a scope variable for convenience.
        $shell = new \Psy\Shell();

        if (method_exists($shell, 'setScopeVariables')) {
            // Modern PsySH supports setScopeVariables to inject variables into REPL scope.
            $shell->setScopeVariables($this->getVars());
        }

        $shell->run();

        return Command::SUCCESS;
    }

    protected function getVars(): array
    {
        return [
            'io' => $this->io,
            'app' => $this->app,
        ];
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
