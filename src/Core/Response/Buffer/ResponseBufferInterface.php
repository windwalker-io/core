<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2019 LYRASOFT.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\Response\Buffer;

/**
 * Interface ResponseBufferInterface
 *
 * @since  3.5
 */
interface ResponseBufferInterface
{
    /**
     * __toString
     *
     * @return  string
     */
    public function __toString();

    /**
     * getMimeType
     *
     * @return  string
     */
    public function getContentType(): string;
}
