<?php

declare(strict_types=1);

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
