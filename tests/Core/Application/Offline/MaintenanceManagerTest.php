<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Application\Offline;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Application\Offline\MaintenanceConfig;
use Windwalker\Core\Application\Offline\MaintenanceManager;

class MaintenanceManagerTest extends TestCase
{
    protected string $file;

    protected MaintenanceManager $instance;

    #[Before]
    protected function setUpInstance(): void
    {
        $this->file = sys_get_temp_dir() . '/ww-down-' . uniqid() . '.json';

        $file = $this->file;

        $this->instance = new class ($file) extends MaintenanceManager {
            public function __construct(protected string $path) {}

            public function getFilePath(): string
            {
                return $this->path;
            }
        };
    }

    #[After]
    protected function removeTempFile(): void
    {
        @unlink($this->file);
    }

    #[Test]
    public function isDownFalseByDefault(): void
    {
        self::assertFalse($this->instance->isDown());
    }

    #[Test]
    public function downWritesConfigAndIsDown(): void
    {
        $this->instance->down(
            (new MaintenanceConfig())->setSecret('abc')->setAllowedIps(['1.2.3.4'])->setRedirect('/x')
        );

        self::assertTrue($this->instance->isDown());
        self::assertFileExists($this->file);

        $config = $this->instance->getConfig();

        self::assertSame('abc', $config->getSecret());
        self::assertSame(['1.2.3.4'], $config->getAllowedIps());
        self::assertSame('/x', $config->getRedirect());
    }

    #[Test]
    public function downAcceptsArray(): void
    {
        $this->instance->down(['secret' => 'arr', 'template' => 'tpl']);

        $config = $this->instance->getConfig();

        self::assertSame('arr', $config->getSecret());
        self::assertSame('tpl', $config->getTemplate());
    }

    #[Test]
    public function upRemovesFileAndIsIdempotent(): void
    {
        $this->instance->down([]);
        $this->instance->up();

        self::assertFalse($this->instance->isDown());

        $this->instance->up();

        self::assertFalse($this->instance->isDown());
    }
}
