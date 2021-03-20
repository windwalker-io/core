<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\View\Event;

/**
 * The AfterRenderEvent class.
 */
class AfterRenderEvent extends AbstractViewRenderEvent
{
    protected string $content;

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param  string  $content
     *
     * @return  static  Return self to support chaining.
     */
    public function setContent(string $content)
    {
        $this->content = $content;

        return $this;
    }
}
