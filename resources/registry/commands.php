<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

return [
    'server:start' => \Windwalker\Core\Command\ServeCommand::class,
    'server:dumper' => \Windwalker\Core\Command\DumpServerCommand::class,

    'cache:clear' => \Windwalker\Core\Command\CacheClearCommand::class,

    'g' => \Windwalker\Core\Generator\Command\GenerateCommand::class,
    'generate:make' => \Windwalker\Core\Generator\Command\GenerateCommand::class,
    'generate:revise' => \Windwalker\Core\Generator\Command\GenReviseCommand::class,

    'mail:test' => \Windwalker\Core\Command\MailTestCommand::class,

    'mig:go' => \Windwalker\Core\Migration\Command\MigrateGoCommand::class,
    'mig:back' => \Windwalker\Core\Migration\Command\MigrateBackCommand::class,
    'mig:reset' => \Windwalker\Core\Migration\Command\ResetCommand::class,
    'mig:status' => \Windwalker\Core\Migration\Command\StatusCommand::class,
    'mig:create' => \Windwalker\Core\Migration\Command\CreateCommand::class,
    'db:export' => \Windwalker\Core\Database\Command\DbExportCommand::class,
    'db:drop-all' => \Windwalker\Core\Database\Command\DbDropAllCommand::class,
    'seed:import' => \Windwalker\Core\Seed\Command\SeedImportCommand::class,
    'seed:clear' => \Windwalker\Core\Seed\Command\SeedClearCommand::class,
    'seed:create' => \Windwalker\Core\Seed\Command\SeedCreateCommand::class,

    'schedule:run' => \Windwalker\Core\Schedule\Command\ScheduleRunCommand::class,
    'schedule:show' => \Windwalker\Core\Schedule\Command\ScheduleShowCommand::class,

    'asset:version' => \Windwalker\Core\Asset\Command\AssetVersionCommand::class,

    'pkg:install' => \Windwalker\Core\Package\Command\PackageInstallCommand::class,

    'run' => \Windwalker\Core\Command\RunCommand::class,

    'build:entity' => \Windwalker\Core\Generator\Command\BuildEntityCommand::class,
    'build:enum' => \Windwalker\Core\Generator\Command\BuildEnumCommand::class,

    'lang:merge' => \Windwalker\Core\Command\LangMergeCommand::class,
    'crypt:key' => \Windwalker\Core\Crypt\Command\CryptKeyCommand::class,

    'queue:worker' => \Windwalker\Core\Queue\Command\QueueWorkerCommand::class,
    'queue:restart' => \Windwalker\Core\Queue\Command\QueueRestartCommand::class,
    'queue:retry' => \Windwalker\Core\Queue\Command\QueueRetryCommand::class,
    'queue:table' => \Windwalker\Core\Queue\Command\QueueTableCommand::class,
    'queue:failed-table' => \Windwalker\Core\Queue\Command\QueueFailedTableCommand::class,

    '_completion' => \Stecman\Component\Symfony\Console\BashCompletion\CompletionCommand::class,
    'auto-complete' => \Windwalker\Core\Command\CompletionCommand::class,
];
