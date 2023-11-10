<?php

declare(strict_types=1);

namespace Windwalker\Core\Error;

use Throwable;

/**
 * The ErrorHandlerInterface class.
 *
 * @since  3.0
 */
interface ErrorHandlerInterface
{
    /**
     * __invoke
     *
     * @param  Throwable  $e
     *
     * @return  void
     */
    public function __invoke(Throwable $e): void;
}
