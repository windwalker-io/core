<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\View;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\View\BaseVM;
use Windwalker\Core\View\View;

#[CoversClass(BaseVM::class)]
class BaseVMTest extends TestCase
{
    #[Test]
    public function prepareReturnsEmptyArray(): void
    {
        $vm = new BaseVM();

        $app = $this->createStub(AppContext::class);
        $view = $this->createStub(View::class);

        self::assertSame([], $vm->prepare($app, $view));
    }
}
