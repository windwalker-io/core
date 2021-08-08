<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Application;

/**
 * Trait WebApplicationTrait
 */
trait WebApplicationTrait
{
    use ApplicationTrait;

    /**
     * Method to check to see if headers have already been sent.
     * We wrap headers_sent() function with this method for testing reason.
     *
     * @return  boolean  True if the headers have already been sent.
     *
     * @see     headers_sent()
     *
     * @since   3.0
     */
    public function checkHeadersSent(): bool
    {
        return headers_sent();
    }

    public function getClient(): string
    {
        return ApplicationInterface::CLIENT_WEB;
    }
}
