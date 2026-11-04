<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\CliServer\PhpNative;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Windwalker\Core\CliServer\PhpNative\PhpNativeEngine;
use Windwalker\Core\Console\ConsoleApplication;

#[CoversClass(PhpNativeEngine::class)]
class PhpNativeEngineTest extends TestCase
{
    #[Test]
    #[TestWith([null, 'public', [], '-t public'])]
    #[TestWith(['router.php', 'public', [], 'router.php'])]
    #[TestWith(['router.php', 'public', ['main' => 'custom.php'], 'custom.php'])]
    #[TestWith([null, 'public', ['docroot' => 'www'], '-t www'])]
    public function runResolvesConfiguredAndRuntimeOptions(
        ?string $main,
        string $docroot,
        array $options,
        string $arguments
    ): void {
        $app = $this->createMock(ConsoleApplication::class);
        $app->expects($this->once())
            ->method('runProcess')
            ->with("php -S localhost:8080 $arguments", '', $this->isCallable());

        $engine = new PhpNativeEngine(
            'main',
            $app,
            new ConsoleOutput(OutputInterface::VERBOSITY_QUIET),
            ['main' => $main, 'docroot' => $docroot, 'state_file' => 'server.json']
        );

        self::assertSame(Command::SUCCESS, $engine->run('localhost', 8080, $options));
    }

    #[Test]
    public function runRejectsInvalidRuntimeOptionsBeforeStartingProcess(): void
    {
        $app = $this->createMock(ConsoleApplication::class);
        $app->expects($this->never())->method('runProcess');

        $engine = new PhpNativeEngine(
            'main',
            $app,
            new ConsoleOutput(OutputInterface::VERBOSITY_QUIET),
            ['main' => null, 'docroot' => 'public']
        );

        $this->expectException(InvalidOptionsException::class);

        $engine->run('localhost', 8080, ['docroot' => 123]);
    }
}
