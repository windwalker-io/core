<?php

declare(strict_types=1);

namespace Windwalker\Core\Generator\Command;

use DomainException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\CompletionContext;
use Windwalker\Console\CompletionHandlerInterface;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Command\CommandPackageResolveTrait;
use Windwalker\Core\Console\ConsoleApplication;
use Windwalker\Core\Database\Command\CommandDatabaseTrait;
use Windwalker\Core\Generator\Builder\EntityMemberBuilder;
use Windwalker\Core\Utilities\ClassFinder;
use Windwalker\Data\Collection;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\DI\Attributes\Autowire;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Utilities\Str;
use Windwalker\Utilities\StrNormalize;

use function Windwalker\collect;
use function Windwalker\ds;

/**
 * The BuildEntityCommand class.
 */
#[CommandWrapper(description: 'Build entity getters/setters and sync properties with database.')]
class BuildEntityCommand implements CommandInterface, CompletionHandlerInterface
{
    use CommandDatabaseTrait;
    use CommandPackageResolveTrait;

    private IOInterface $io;

    /**
     * BuildEntityCommand constructor.
     */
    public function __construct(
        #[Autowire] protected ClassFinder $classFinder,
        protected ConsoleApplication $app,
    ) {
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
            'pkg',
            null,
            InputOption::VALUE_REQUIRED,
            'The package name to find namespace.'
        );

        $command->addOption(
            'props',
            'p',
            InputOption::VALUE_NONE,
            'Generate properties'
        );

        $command->addOption(
            'hooks',
            'k',
            InputOption::VALUE_NONE,
            'Generate property hooks'
        );

        $command->addOption(
            'methods',
            'm',
            InputOption::VALUE_NONE,
            'Generate methods'
        );

        $command->addOption(
            'auto-gen-enums',
            'e',
            InputOption::VALUE_NEGATABLE,
            'Should auto generate enums or not.',
        );

        $command->addOption(
            'dry-run',
            'd',
            InputOption::VALUE_NONE,
            'Do not replace origin file.'
        );

        $this->configureDatabaseOptions($command);

        // phpcs:disable
        $command->setHelp(
            <<<HELP
            $ <info>php windwalker build:entity Foo</info> => Use short name, will auto build App\\Entity\\Foo class
            $ <info>php windwalker build:entity App\\Entity\\Foo</info> => Use full name, will auto build App\\Entity\\Foo class
            $ <info>php windwalker build:entity App\\Entity</info> => Not an exists class, will build all App\\Entity\\* classes
            $ <info>php windwalker build:entity "App\\Entity\\*"</info> => Use wildcards, will build all App\\Entity\\* classes
            HELP
        );
        // phpcs:enable
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
            throw new DomainException('Please install windwalker/database first.');
        }

        $this->io = $io;

        $ns = $io->getArgument('ns');
        $connection = $io->getOption('connection') ?: null;

        $props = $this->io->getOption('props');
        $methods = $this->io->getOption('methods');
        $hooks = $this->io->getOption('hooks');

        $runDefer = false;

        if ($props === true && ($methods === true || $hooks === true)) {
            $runDefer = true;
            $methods = false;
            $hooks = false;
        } elseif ($props === false && $methods === false && $hooks === false) {
            $props = true;
        }

        $options = compact('props', 'methods', 'hooks');

        if ($ns === '*') {
            $ns = $this->getPackageNamespace($io, 'Entity') ?? 'App\\Entity\\';
            $ns .= '*';
        }

        if (str_contains($ns, '*')) {
            $ns = Str::removeRight($ns, '\\*');
            $ns = StrNormalize::toClassNamespace($ns);
            $classes = $this->classFinder->findClasses($ns);
            $this->handleClasses($classes, $connection, $options);

            return 0;
        }

        if (!class_exists($ns)) {
            $baseNs = $this->getPackageNamespace($io, 'Entity') ?? 'App\\Entity\\';
            $ns = $baseNs . $ns;
        }

        if (!class_exists($ns)) {
            $classes = $this->classFinder->findClasses($ns);
            $this->handleClasses($classes, $connection, $options);

            return 0;
        }

        $classes = [$ns];

        $this->handleClasses($classes, $connection, $options);

        if ($runDefer) {
            $command = [
                'build:entity',
                $io->getArgument('ns') ?: '',
                ($pkg = $io->getOption('pkg')) ? "--pkg=$pkg" : '',
                $connection ? "--connection=$connection" : '',
                $io->getOption('methods') ? "--methods" : '',
                $io->getOption('hooks') ? "--hooks" : '',
                $io->getOption('dry-run') ? '--dry-run' : '',
            ];

            $process = $this->app->runProcess(
                '@php windwalker ' . implode(' ', $command),
                null,
                $io->getOutput()
            );

            return $process->getExitCode();
        }

        return 0;
    }

    protected function handleClasses(iterable $classes, ?string $connection, array $options = []): void
    {
        $orm = $this->databaseManager->get($connection)->orm();

        $newEnums = [];

        foreach ($classes as $class) {
            if (!class_exists($class)) {
                continue;
            }

            $this->io->newLine();
            $this->io->writeln("Handling: <info>$class</info>");

            $builder = new EntityMemberBuilder($meta = $orm->getEntityMetadata($class));
            $builder->addEventDealer($this->app);
            $newCode = $builder->process(
                $options,
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

            if ($added['hooks']) {
                $this->io->newLine();
                $this->io->writeln('  Added property hooks:');

                foreach ($added['hooks'] as $propName => $hooks) {
                    if (count($hooks) > 0) {
                        $hooksStr = collect($hooks)->map(fn($hook) => "<info>$hook</info>")->implode('|');
                        $this->io->writeln("    - $propName ($hooksStr)");
                    }
                }
            }

            $newEnums = [...$newEnums, ...$added['enums']];
        }

        if ($newEnums === []) {
            return;
        }

        $this->io->newLine();
        $this->io->style()->title('NEW ENUMS:');

        foreach ($newEnums as [$newEnum, $cases]) {
            $this->io->writeln(
                "  - <info>$newEnum</info>" . ($cases ? " ($cases)" : '')
            );
        }

        $this->io->newLine();
        $autoGen = $this->io->getOption('auto-gen-enums')
            ?? $this->io->askConfirmation('Do you want to auto generate enums? (Y/n): ', true);

        if ($autoGen) {
            foreach ($newEnums as [$newEnum, $cases]) {
                // Separate namespace and class name
                $parts = explode('\\', $newEnum);
                $shortName = array_pop($parts);
                $namespace = implode('\\', $parts);

                $casesOptions = $cases ? '--case="' . $cases . '"' : '';

                // Create the new enum class
                $this->app->runProcess(
                    sprintf(
                        'php windwalker g enum %s %s --ns="%s"',
                        $shortName,
                        $casesOptions,
                        $namespace
                    ),
                    output: $this->io,
                );
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function completeOptionValues($optionName, CompletionContext $context)
    {
    }

    public function handleCompletions(CompletionContext $context): ?array
    {
        if ($context->isArgument() && $context->name === 'ns') {
            $ns = $this->getPackageNamespace($context->io, 'Entity') ?? 'App\\Entity\\';

            $classes = iterator_to_array($this->classFinder->findClasses($ns));

            return collect($classes)
                ->map(fn(string $className) => (string) Collection::explode('\\', $className)->pop())
                ->dump();
        }

        if ($context->isOption() && $context->name === 'pkg') {
            $packages = $this->packageRegistry->getPackagesKeyByName();

            return array_keys($packages);
        }

        return null;
    }
}
