<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Generator\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Generator\Builder\EntityMemberBuilder;
use Windwalker\Core\Utilities\ClassFinder;
use Windwalker\DI\Attributes\Autowire;
use Windwalker\Filesystem\Filesystem;
use Windwalker\ORM\ORM;
use Windwalker\Utilities\Str;

/**
 * The BuildEntityCommand class.
 */
#[CommandWrapper(description: 'Build entity getters/setters and sync properties with database.')]
class BuildEntityCommand implements CommandInterface
{
    private IOInterface $io;

    /**
     * BuildEntityCommand constructor.
     */
    public function __construct(#[Autowire] protected ClassFinder $classFinder, protected ORM $orm)
    {
    }

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
            'ns',
            InputArgument::REQUIRED,
            'The entity class or namespace.'
        );

        $command->addOption(
            'no-props',
            null,
            InputOption::VALUE_OPTIONAL,
            'Dont\'t generate properties',
            false
        );

        $command->addOption(
            'no-methods',
            null,
            InputOption::VALUE_OPTIONAL,
            'Don\'t generate methods',
            false
        );

        $command->addOption(
            'dry-run',
            'd',
            InputOption::VALUE_NONE,
            'Do not replace origin file.'
        );
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
        $this->io = $io;

        $ns = $io->getArgument('ns');

        if (!class_exists($ns) || str_contains($ns, '*')) {
            $ns      = Str::removeRight($ns, '\\*');
            $classes = $this->classFinder->findClasses($ns);
        } else {
            $classes = [$ns];
        }

        $this->handleClasses($classes);

        return 0;
    }

    protected function handleClasses(iterable $classes): void
    {
        foreach ($classes as $class) {
            if (!class_exists($class)) {
                continue;
            }

            $this->io->newLine();
            $this->io->writeln("Handling: <info>$class</info>");

            $builder = new EntityMemberBuilder($meta = $this->orm->getEntityMetadata($class));
            $newCode = $builder->process(
                [
                    'props' => $this->io->getOption('no-props') === false,
                    'methods' => $this->io->getOption('no-methods') === false,
                ],
                $added
            );

            if (!$this->io->getOption('dry-run')) {
                Filesystem::write(
                    $meta->getReflector()->getFileName(),
                    $newCode
                );
            }

            if ($added['properties']) {
                $this->io->newLine();
                $this->io->writeln('  Added columns:');

                foreach ($added['properties'] as [$property, $column]) {
                    $this->io->writeln("    - <info>$property</info> (<fg=gray>$column</>)");
                }
            }

            if ($added['methods']) {
                $this->io->newLine();
                $this->io->writeln('  Added methods:');

                foreach ($added['methods'] as $method) {
                    $this->io->writeln("    - <info>$method</info>");
                }
            }
        }
    }
}
