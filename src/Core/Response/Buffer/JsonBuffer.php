<?php

/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

declare(strict_types=1);

namespace Windwalker\Core\Response\Buffer;

use JsonException;

/**
 * The JsonBuffer class.
 *
 * @since  3.0
 */
class JsonBuffer extends AbstractBuffer
{
    /**
     * Method for sending the response in JSON format
     *
     * @return  string  The response in JSON format
     * @throws JsonException
     */
    public function toString(): string
    {
        return json_encode(get_object_vars($this), JSON_THROW_ON_ERROR);
    }

    /**
     * getMimeType
     *
     * @return  string
     */
    public function getContentType(): string
    {
        return 'application/json';
    }
}
