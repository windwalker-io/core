<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\Mailer\Adapter;

/**
 * Interface LongConnectionAdapter
 *
 * @since  3.5.23.5
 */
interface LongConnectionInterface
{
    /**
     * disconnect
     *
     * @return  void
     *
     * @since  3.5.23.5
     */
    public function disconnect(): void;
}
