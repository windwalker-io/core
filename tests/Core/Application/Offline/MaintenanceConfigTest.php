<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Application\Offline;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Application\Offline\MaintenanceConfig;

class MaintenanceConfigTest extends TestCase
{
    #[Test]
    public function addAllowedIpsMerges(): void
    {
        $c = new MaintenanceConfig()->setAllowedIps(['1.1.1.1']);
        $c->addAllowedIps('2.2.2.2', '3.3.3.3');

        self::assertSame(['1.1.1.1', '2.2.2.2', '3.3.3.3'], $c->getAllowedIps());
    }

    #[Test]
    public function setRedirectAcceptsStringable(): void
    {
        $c = new MaintenanceConfig();
        $c->setRedirect(new class () {
            public function __toString(): string
            {
                return '/to';
            }
        });

        self::assertSame('/to', $c->getRedirect());
    }
}
