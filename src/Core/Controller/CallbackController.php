<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\Controller;

/**
 * The CallbackController class.
 *
 * @since  3.5.23.5
 */
class CallbackController extends AbstractController
{
    /**
     * @var callable
     */
    protected $callback;

    /**
     * @inheritDoc
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;

        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function doExecute()
    {
        return $this->container->call($this->callback);
    }
}
