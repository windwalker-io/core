<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Controller;

/**
 * The CallbackController class.
 *
 * @since  __DEPLOY_VERSION__
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
