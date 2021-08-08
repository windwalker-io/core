<?php

/**
 * Part of unicorn project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    LGPL-2.0-or-later
 */

declare(strict_types=1);

namespace Windwalker\Core\Migration\Exception;

use Throwable;
use Windwalker\Core\Migration\Migration;

/**
 * The MigrationExistsException class.
 */
class MigrationExistsException extends \RuntimeException
{
    /**
     * Construct the exception. Note: The message is NOT binary safe.
     * @link https://php.net/manual/en/exception.construct.php
     *
     * @param  string          $message   [optional] The Exception message to throw.
     * @param  int             $code      [optional] The Exception code.
     * @param  null|Throwable  $previous  [optional] The previous throwable used for the exception chaining.
     */
    public function __construct(protected Migration $migration, $message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return Migration
     */
    public function getMigration(): Migration
    {
        return $this->migration;
    }
}
