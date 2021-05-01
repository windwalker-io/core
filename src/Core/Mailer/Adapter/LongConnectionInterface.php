<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Mailer\Adapter;

/**
 * Interface LongConnectionAdapter
 *
 * @since  __DEPLOY_VERSION__
 */
interface LongConnectionInterface
{
    /**
     * disconnect
     *
     * @return  void
     *
     * @since  __DEPLOY_VERSION__
     */
    public function disconnect(): void;
}
