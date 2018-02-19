<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Object;

/**
 * The Null Object Interface
 */
interface NullObjectInterface extends SilencerObjectInterface
{
    /**
     * Is this object not contain any values.
     *
     * @return boolean
     */
    public function isNull();

    /**
     * Is this object not contain any values.
     *
     * @return  boolean
     */
    public function notNull();
}
