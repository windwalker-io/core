<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * The LoggerAwareTrait class.
 *
 * @since  3.0
 */
trait LoggerAwareTrait
{
    /**
     * Property logger.
     *
     * @var  LoggerInterface
     */
    protected $logger;

    /**
     * Sets a logger.
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * getLogger
     *
     * @return  LoggerInterface
     */
    public function getLogger()
    {
        if (!$this->logger instanceof LoggerInterface) {
            $this->logger = new NullLogger;
        }

        return $this->logger;
    }
}
