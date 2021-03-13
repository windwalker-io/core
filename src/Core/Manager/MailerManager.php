<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Manager;

/**
 * The MailerManager class.
 */
class MailerManager extends AbstractManager
{
    public function getConfigPrefix(): string
    {
        return 'mail';
    }
}
