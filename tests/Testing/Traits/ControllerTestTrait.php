<?php

declare(strict_types=1);

namespace Windwalker\Tests\Testing\Traits;

use Psr\Http\Message\ServerRequestInterface;
use Tests\TestApp;

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
        return TestApp::$appContext->dispatchWithInput(
            $controller,
            $data,
            $args
        );
    }
}
