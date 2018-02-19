<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Controller\Traits;

use Windwalker\Core\Controller\Middleware\CsrfProtectionMiddleware;
use Windwalker\Utilities\Queue\PriorityQueue;

/**
 * The CsrfProtectionTrait class.
 *
 * @since  3.0
 */
trait CsrfProtectionTrait
{
    /**
     * bootCsrfProtectionTrait
     *
     * @return  void
     */
    public function bootCsrfProtectionTrait()
    {
        $this->addMiddleware(CsrfProtectionMiddleware::class, PriorityQueue::HIGH);
    }

    /**
     * csrfProtect
     *
     * @param bool $bool
     *
     * @return  static
     */
    public function csrfProtect($bool = null)
    {
        if ($bool === null) {
            return $this->config->get('csrf_protect', true);
        }

        $this->config->set('csrf_protect', (bool) $bool);

        return $this;
    }
}
