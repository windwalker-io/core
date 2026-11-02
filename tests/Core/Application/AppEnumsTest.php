<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Application;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Application\AppClient;
use Windwalker\Core\Application\AppLayer;
use Windwalker\Core\Application\AppType;

class AppEnumsTest extends TestCase
{
    #[Test]
    public function appTypeChecks(): void
    {
        self::assertTrue(AppType::WEB->isWeb());
        self::assertFalse(AppType::WEB->isCliWeb());
        self::assertFalse(AppType::WEB->isConsole());
        self::assertTrue(AppType::CLI_WEB->isCliWeb());
        self::assertFalse(AppType::CLI_WEB->isWeb());
        self::assertTrue(AppType::CONSOLE->isConsole());
        self::assertFalse(AppType::CONSOLE->isWeb());
    }

    #[Test]
    public function appClientChecks(): void
    {
        self::assertTrue(AppClient::WEB->isWeb());
        self::assertFalse(AppClient::WEB->isConsole());
        self::assertTrue(AppClient::CONSOLE->isConsole());
        self::assertFalse(AppClient::CONSOLE->isWeb());
    }

    #[Test]
    public function appLayerOrder(): void
    {
        self::assertLessThan(AppLayer::APP, AppLayer::RUNTIME);
        self::assertLessThan(AppLayer::REQUEST, AppLayer::APP);
    }
}
