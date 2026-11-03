<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Seed;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Generator\CodeGenerator;
use Windwalker\Core\Generator\FileCollection;
use Windwalker\Core\Seed\FakerService;
use Windwalker\Core\Seed\SeedService;
use Windwalker\Core\Testing\Stub\ApplicationTraitStub;

/**
 * Covers the parts of SeedService that do not require a real host app /
 * database / Migration-style fixture files. `import()`/`clear()` are
 * exercised through the same recipe as `MigrationServiceTest` and are not
 * duplicated here; see `.github/docs/integration-test-backlog.md` for the
 * remaining `copySeedFile()` integration gap (real `.tpl` rendering).
 */
#[CoversClass(SeedService::class)]
class SeedServiceTest extends TestCase
{
    #[Test]
    public function buildReplacementDataReturnsNameAndYearFromGivenDate(): void
    {
        $service = $this->createSeedService();

        $data = $service->buildReplacementData('CreateUserData', new DateTimeImmutable('2030-05-01'));

        self::assertSame(
            [
                'name' => 'CreateUserData',
                'year' => '2030',
            ],
            $data
        );
    }

    #[Test]
    public function buildReplacementDataDefaultsYearToNow(): void
    {
        $service = $this->createSeedService();

        $data = $service->buildReplacementData('CreateUserData');

        self::assertSame('CreateUserData', $data['name']);
        self::assertSame(date('Y'), $data['year']);
    }

    #[Test]
    public function copySeedFileDelegatesToCodeGeneratorWithBuiltReplacementData(): void
    {
        $fileCollection = $this->createStub(FileCollection::class);

        $codeGenerator = $this->createMock(CodeGenerator::class);
        $codeGenerator->expects($this->once())
            ->method('from')
            ->with('/templates/seeder.tpl')
            ->willReturn($fileCollection);

        $fileCollection->method('replaceTo')
            ->willReturn($fileCollection);

        $service = $this->createSeedService($codeGenerator);

        $result = $service->copySeedFile('/dest', 'CreateUserData', '/templates/seeder.tpl');

        self::assertSame($fileCollection, $result);
    }

    #[Test]
    public function copySeedFilePassesBuiltReplacementDataToReplaceTo(): void
    {
        $fileCollection = $this->createMock(FileCollection::class);
        $fileCollection->expects($this->once())
            ->method('replaceTo')
            ->with(
                '/dest',
                self::callback(
                    static fn(array $data) => $data['name'] === 'CreateUserData'
                        && $data['year'] === date('Y')
                )
            )
            ->willReturn($fileCollection);

        $codeGenerator = $this->createStub(CodeGenerator::class);
        $codeGenerator->method('from')->willReturn($fileCollection);

        $service = $this->createSeedService($codeGenerator);

        $service->copySeedFile('/dest', 'CreateUserData', '/templates/seeder.tpl');
    }

    private function createSeedService(?CodeGenerator $codeGenerator = null): SeedService
    {
        return new SeedService(
            new ApplicationTraitStub(),
            new FakerService(),
            $codeGenerator ?? $this->createStub(CodeGenerator::class),
        );
    }
}
