<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Migration\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Command\Command as ConsoleCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Windwalker\Console\IO;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Migration\Command\StatusCommand;
use Windwalker\Core\Migration\MigrationService;

#[CoversClass(StatusCommand::class)]
class StatusCommandTest extends TestCase
{
    #[Test]
    public function databaseCreationFailureWarnsAndRendersEmptyStatusTable(): void
    {
        $migrationService = $this->createMock(MigrationService::class);
        $migrationService->expects($this->once())
            ->method('getMigrations')
            ->willReturn([]);
        $migrationService->expects($this->never())
            ->method('getVersions');

        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())
            ->method('make')
            ->with(MigrationService::class)
            ->willReturn($migrationService);

        $statusCommand = new class () extends StatusCommand {
            public function setApplication(ApplicationInterface $app): void
            {
                $this->app = $app;
            }

            protected function preprocess(IOInterface $io, int $options = 0): void
            {
                if ($options & static::CREATE_DATABASE) {
                    throw new RuntimeException('Database creation failed.');
                }
            }

            public function getMigrationFolder(IOInterface $io): string
            {
                return '';
            }
        };
        $statusCommand->setApplication($app);

        $consoleCommand = new ConsoleCommand('migration-status');
        $statusCommand->configure($consoleCommand);
        $input = new ArrayInput([], $consoleCommand->getDefinition());
        $output = new BufferedOutput();
        $io = new IO($input, $output, $consoleCommand);

        self::assertSame(0, $statusCommand->execute($io));

        $contents = $output->fetch();
        self::assertStringContainsString('Database creation failed.', $contents);
        self::assertStringContainsString('No migrations found.', $contents);
        self::assertStringContainsString('Migration Status', $contents);
        self::assertStringContainsString('Migration Name', $contents);
    }
}
