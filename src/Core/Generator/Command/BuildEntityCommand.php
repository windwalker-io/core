<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
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
use Windwalker\Database\DatabaseAdapter;
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
    public function __construct(#[Autowire] protected ClassFinder $classFinder, protected ?ORM $orm = null)
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
            'props',
            'p',
            InputOption::VALUE_NONE,
            'Generate properties'
        );

        $command->addOption(
            'methods',
            'm',
            InputOption::VALUE_NONE,
            'Generate methods'
        );

        $command->addOption(
            'dry-run',
            'd',
            InputOption::VALUE_NONE,
            'Do not replace origin file.'
        );

        $command->setHelp(
            <<<HELP
            $ <info>php windwalker build:entity Foo</info> => Use short name, will auto build App\\Entity\\Foo class
            $ <info>php windwalker build:entity App\\Entity\\Foo</info> => Use full name, will auto build App\\Entity\\Foo class
            $ <info>php windwalker build:entity App\\Entity</info> => Not an exists class, will build all App\\Entity\\* classes
            $ <info>php windwalker build:entity "App\\Entity\\*"</info> => Use wildcards, will build all App\\Entity\\* classes
            HELP
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
        if (!class_exists(DatabaseAdapter::class)) {
            throw new \DomainException('Please install windwalker/database first.');
        }

        $this->io = $io;

        $ns = $io->getArgument('ns');

        if (str_contains($ns, '*')) {
            $ns      = Str::removeRight($ns, '\\*');
            $classes = $this->classFinder->findClasses($ns);
            $this->handleClasses($classes);
            return 0;
        }

        if (!class_exists($ns)) {
            $ns = 'App\\Entity\\' . $ns;
        }

        if (!class_exists($ns)) {
            $classes = $this->classFinder->findClasses($io->getArgument('ns'));
            $this->handleClasses($classes);
            return 0;
        }

        $classes = [$ns];

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

            $props = $this->io->getOption('props');
            $methods = $this->io->getOption('methods');

            if ($props === false && $methods === false) {
                $props = true;
            }

            $builder = new EntityMemberBuilder($meta = $this->orm->getEntityMetadata($class));
            $newCode = $builder->process(
                compact('props', 'methods'),
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
