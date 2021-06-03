<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Command;

use Composer\Autoload\ClassLoader;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Reflection\ClassReflection;
use PhpParser\Node;
use PhpParser\Node\VarLikeIdentifier;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Utilities\ClassFinder;
use Windwalker\DI\Attributes\Autowire;
use Windwalker\Filesystem\Path;
use Windwalker\Utilities\Str;

/**
 * The BuildEntityCommand class.
 */
#[CommandWrapper(description: 'Build entity getters/setters and sync with database.')]
class BuildEntityCommand implements CommandInterface
{
    /**
     * BuildEntityCommand constructor.
     */
    public function __construct(#[Autowire] protected ClassFinder $classFinder)
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
            'root',
            null,
            InputOption::VALUE_REQUIRED,
            'The root dir of namespace',
            'src'
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
        $ns = $io->getArgument('ns');

        $classes = $this->classFinder->findClasses($ns);

        foreach ($classes as $class) {
            // $cg = ClassGenerator::fromReflection(new ClassReflection($class));
            // show($cg->getProperties()['id']->att, 2);
            $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
            $ast = $parser->parse(file_get_contents((new \ReflectionClass($class))->getFileName()));

            $traverser = new NodeTraverser;
            $traverser->addVisitor(new class extends NodeVisitorAbstract {
                public function leaveNode(Node $node) {
                    if ($node instanceof VarLikeIdentifier) {
                        show($node);
                    }
                }
            });
            $traverser->traverse($ast);
exit(' @Checkpoint');
show($ast);

            exit(' @Checkpoint');
        }

        return 0;
    }
}
