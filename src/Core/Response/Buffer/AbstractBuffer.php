<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Response\Buffer;

/**
 * The AbstractBuffer class.
 *
 * @since  3.0
 */
abstract class AbstractBuffer implements ResponseBufferInterface
{
    public int $status = 200;

    /**
     * Constructor
     *
     * @param  string  $message
     * @param  mixed   $data
     * @param  bool    $success
     * @param  int     $code
     */
    public function __construct(
        public string $message = '',
        public mixed $data = null,
        public bool $success = true,
        public int $code = 200
    ) {
    }

    /**
     * toString
     *
     * @return  string
     */
    abstract public function toString(): string;

    /**
     * __toString
     *
     * @return  string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * getMimeType
     *
     * @return  string
     */
    abstract public function getContentType(): string;
}
