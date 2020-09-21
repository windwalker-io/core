<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Interface ControllerInterface
 */
interface ControllerInterface
{
    /**
     * execute
     *
     * @param  ServerRequestInterface  $request
     * @param  string                  $task
     *
     * @return  mixed|ResponseInterface
     * @throws \ReflectionException
     */
    public function execute(ServerRequestInterface $request, string $task);
}
