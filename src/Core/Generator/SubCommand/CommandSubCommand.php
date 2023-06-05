<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Generator\SubCommand;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Generator\Builder\CallbackAstBuilder;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Utilities\Str;

/**
 * The CommandSubCommand class.
 */
#[CommandWrapper(description: 'Generate Windwalker command class.')]
class CommandSubCommand extends AbstractGeneratorSubCommand
{
    protected string $defaultNamespace = 'App\\Command';

    protected string $defaultDir = 'src/Command';

    protected bool $requireDest = false;

    /**
     * configure
     *
     * @param  Command  $command
     *
     * @return  void
     */
    public function configure(Command $command): void
    {
        parent::configure($command);

        $command->addOption(
            'desc',
            null,
            InputOption::VALUE_REQUIRED,
            'The Command description.'
        );

        $command->addOption(
            'auto-register',
            'a',
            InputOption::VALUE_OPTIONAL,
            'Auto register to commands.'
        );
    }

    /**
     * Interaction with user.
     *
     * @param  IOInterface  $io
     *
     * @return  void
     */
    public function interact(IOInterface $io): void
    {
        parent::interact($io);

        if (!$io->getOption('auto-register')) {
            $io->setOption(
                'auto-register',
                $io->ask("Enter command name to auto-register command. (Leave empty to ignore.):\n") ?: false
            );
        }
    }

    /**
     * Executes the current command.
     *
     * @param  IOInterface  $io
     *
     * @return  int Return 0 is success, 1-255 is failure.
     */
    public function execute(IOInterface $io): int
    {
        [, $name] = $this->getNameParts($io);
        $force = $io->getOption('force');

        if (!$name) {
            $io->errorStyle()->error('No command name');

            return 255;
        }

        $this->codeGenerator->from($this->getViewPath('command/*'))
            ->replaceTo(
                $this->getDestPath($io),
                [
                    'className' => $className = Str::ensureRight($name, 'Command'),
                    'name' => Str::removeRight($name, 'Command'),
                    'ns' => $ns = $this->getNamesapce($io),
                    'desc' => $io->getOption('desc'),
                ],
                $force
            );

        $fqn = '\\' . $ns . '\\' . $className;

        if ($cname = $io->getOption('auto-register')) {
            $hasChanged = false;
            $file = $this->app->path('@resources/registry/commands.php');
            $builder = new CallbackAstBuilder(file_get_contents($file));
            $factory = $builder->createNodeFactory();
            $builder->leaveNode(
                function (Node $node) use ($io, $cname, $fqn, $factory, &$hasChanged) {
                    if ($node instanceof Node\Expr\ArrayItem) {
                        if ((string) $node->key->value === $cname) {
                            $io->style()->warning("Command name: $cname exists.");

                            return NodeTraverser::STOP_TRAVERSAL;
                        }
                    }

                    if ($node instanceof Node\Expr\Array_) {
                        $node->items[] = new Node\Expr\ArrayItem(
                            $factory->classConstFetch($fqn, 'class'),
                            new Node\Scalar\String_($cname)
                        );
                        $hasChanged = true;
                    }
                }
            );

            $newCode = $builder->process();

            if ($hasChanged) {
                Filesystem::write($file, $newCode);

                $io->writeln("[<info>ADDED</info>] Register <fg=yellow>$cname</> command to file: <info>$file</info>");
            }
        }

        return 0;
    }
}
