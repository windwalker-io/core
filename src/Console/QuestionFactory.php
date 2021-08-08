<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Console;

use Windwalker\DI\Container;

/**
 * The QuestionFactory class.
 */
class QuestionFactory
{
    /**
     * QuestionFactory constructor.
     *
     * @param  Container  $container
     */
    public function __construct(protected Container $container)
    {
    }

    public function create()
    {

    }
}
