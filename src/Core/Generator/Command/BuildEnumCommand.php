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
use Windwalker\Core\Generator\Builder\EnumBuilder;
use Windwalker\Core\Utilities\ClassFinder;
use Windwalker\DI\Attributes\Autowire;
use Windwalker\Filesystem\Filesystem;
use Windwalker\ORM\ORM;
use Windwalker\Utilities\Str;

/**
 * The BuildEntityCommand class.
 */
#[CommandWrapper(description: 'Build enum docblock.')]
class BuildEnumCommand implements CommandInterface
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
            'The enum class or namespace.'
        );

        $command->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Do not save class.'
        );

        $command->setHelp(
            <<<HELP
            $ <info>php windwalker build:enum Foo</info> => Use short name, will auto build App\\Enum\\Foo class
            $ <info>php windwalker build:enum App\\Enum\\Foo</info> => Use full name, will auto build App\\Enum\\Foo class
            $ <info>php windwalker build:enum App\\Enum</info> => Not an exists class, will build all App\\Enum\\* classes
            $ <info>php windwalker build:enum "App\\Enum\\*"</info> => Use wildcards, will build all App\\Enum\\* classes
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
        $this->io = $io;

        $ns = $io->getArgument('ns');

        if (str_contains($ns, '*')) {
            $ns      = Str::removeRight($ns, '\\*');
            $classes = $this->classFinder->findClasses($ns);
            $this->handleClasses($classes);
            return 0;
        }

        if (!class_exists($ns)) {
            $ns = 'App\\Enum\\' . $ns;
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

            $ref = new \ReflectionClass($class);

            $content = file_get_contents($ref->getFileName());

            $newCode = preg_replace_callback(
                // @see https://stackoverflow.com/a/29290586
                '/(?:\/\*(?:[^*]|(?:\*[^\/]))*\*\/)\s+class/',
                function (array $matches) use ($ref) {
                    $methods = [];

                    foreach ($ref->getConstants() as $constant => $value) {
                        $this->io->writeln(' - ' . $constant);
                        $methods[] = " * @method static \$this {$constant}()";
                    }

                    $methods = implode("\n", $methods);

                    return <<<PHP
                        /**
                         * The {$ref->getShortName()} enum class.
                         * 
                        $methods
                         */
                        class
                        PHP;
                },
                $content
            );

            if (!$this->io->getOption('dry-run')) {
                Filesystem::write(
                    $ref->getFileName(),
                    $newCode
                );
            }
        }
    }
}
