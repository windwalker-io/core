<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Application\Stub;

use Windwalker\Core\Application\Context\AppRequestTrait;

/**
 * Minimal host for AppRequestTrait so input()'s non-array/scalar compileInput()
 * fallback branches can be exercised directly, without a real AppRequest or
 * WsAppRequest (the latter needs the uninstalled windwalker/reactor package).
 */
class AppRequestTraitStub
{
    use AppRequestTrait;

    public function __construct(private mixed $compiledInput) {}

    protected function getUriQueryValues(): mixed
    {
        return [];
    }

    public function getUrlVars(): array
    {
        return [];
    }

    protected function compileInput(): mixed
    {
        return $this->compiledInput;
    }
}
