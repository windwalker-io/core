<?php

declare(strict_types=1);

namespace Windwalker\Core\Migration\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\InteractInterface;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Migration\MigrationService;
use Windwalker\Core\Utilities\ClassFinder;
use Windwalker\DI\Attributes\Autowire;
use Windwalker\Utilities\Str;

/**
 * The CreateCommand class.
 */
#[CommandWrapper(description: 'Create a migration version.')]
class CreateCommand extends AbstractMigrationCommand implements InteractInterface
{
    /**
     * CreateCommand constructor.
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
        parent::configure($command);

        $command->addArgument(
            'name',
            InputArgument::REQUIRED,
            'Migration name',
        );

        $command->addArgument(
            'entity',
            InputArgument::OPTIONAL,
            'Entity name',
        );

        $command->addOption(
            'update',
            'u',
            InputOption::VALUE_NEGATABLE,
            'Is an update or create new entity',
        );
    }

    public function interact(IOInterface $io): void
    {
        if (!$io->getArgument('entity')) {
            $classes = iterator_to_array($this->classFinder->findClasses('App\\Entity'));
            $ignore = '** IGNORE **';
            array_unshift($classes, $ignore);

            $value = $io->askChoice(
                '<question>Entity name?</question>',
                $classes,
                '0'
            );

            if ($value !== $ignore) {
                $io->setArgument('entity', $value);
            }
        }
        // $io->getArgument('entity') ?: $io->ask(
        //     new Question(
        //         '<question>Entity name?</question> [default: Table]',
        //         ''
        //     )->setAutocompleterCallback(
        //         function (string $input) {
        //             $classes = $this->classFinder->findClasses('App\\Entity');
        //             $classes = iterator_to_array($classes);
        //
        //             return array_map(
        //                 fn ($class) => Str::removeLeft($class, 'App\\Entity\\'),
        //                 $classes
        //             );
        //
        //             // return array_filter(
        //             //     $classes,
        //             //     static fn($class) => str_contains(
        //             //         Str::removeLeft(strtolower($class), 'App\\Entity\\'),
        //             //         strtolower($input)
        //             //     )
        //             // );
        //         }
        //     )
        // );
        if ($io->getOption('update') === null) {
            $update = $io->askChoice(
                '<question>Is an update or create new entity?</question>',
                [
                    '1' => 'create',
                    '2' => 'update',
                ],
                '1'
            );

            $io->setOption('update', $update === 'update');
        }
    }

    /**
     * Executes the current command.
     *
     * @param  IOInterface  $io
     *
     * @return  mixed
     */
    public function execute(IOInterface $io): int
    {
        $name = $io->getArgument('name');
        $entity = $io->getArgument('entity');
        $update = $io->getOption('update');

        if ($entity && !str_contains($entity, '\\')) {
            $entity = Str::ensureLeft($entity, '\\App\\Entity\\');
        }

        if ($entity) {
            $entity = Str::ensureLeft($entity, '\\');
        }

        $dir = $update ? 'update' : 'create';

        $migrationService = $this->app->make(MigrationService::class);

        $migrationService->copyMigrationFile(
            $this->getMigrationFolder($io),
            $name,
            __DIR__ . "/../../../../resources/templates/migration/$dir/*",
            compact('entity')
        );

        return 0;
    }
}
