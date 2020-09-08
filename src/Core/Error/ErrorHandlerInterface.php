<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Error;

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
     * @param  \Throwable $e
     *
     * @return  void
     */
    public function __invoke(\Throwable $e): void;
}
