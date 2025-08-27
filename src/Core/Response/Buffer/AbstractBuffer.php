<?php

declare(strict_types=1);

namespace Windwalker\Core\Response\Buffer;

/**
 * The AbstractBuffer class.
 *
 * @since  3.0
 */
abstract class AbstractBuffer implements ResponseBufferInterface
{
    public int $status = 200;
    public function __construct(
        public string $message = '',
        public mixed $data = null,
        public bool $success = true,
        public int|string $code = 200
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
