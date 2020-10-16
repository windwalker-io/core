<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Http;

use Psr\Http\Message\ServerRequestInterface;

/**
 * The Input class.
 */
class Input
{
    protected array $data = [];

    /**
     * Input constructor.
     *
     * @param  ServerRequestInterface  $request
     */
    public function __construct(protected ServerRequestInterface $request)
    {
    }

    public function getData(): array
    {
        return $this->data ??= A
    }
}
