<?php

declare(strict_types=1);

namespace Windwalker\Core\Testing\Traits;

use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Testing\AbstractTestApp;

/**
 * Dispatches a controller through the shared app-backed test context.
 *
 * Requires the consuming test suite to have a {@see AbstractTestApp}
 * subclass bootstrapped and `AbstractTestApp::$ctx` prepared (see that
 * class's docblock and `tests/TestApp.php` / `tests/bootstrap.app.php` in
 * this package for the reference setup).
 */
trait ControllerTestTrait
{
    /**
     * @param  string|array|callable              $controller  The controller pointer.
     * @param  ServerRequestInterface|array|null  $data        The input data, can be array or Request object.
     * @param  array                              $args        The method arguments if not injectable.
     *
     * @return  mixed
     */
    protected function dispatchController(
        string|array|callable $controller,
        ServerRequestInterface|array|null $data = null,
        array $args = [],
    ): mixed {
        return AbstractTestApp::$ctx->dispatchWithInput(
            $controller,
            $data,
            $args
        );
    }
}
