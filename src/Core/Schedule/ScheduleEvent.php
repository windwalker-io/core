<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2019 .
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Schedule;

use Cron\CronExpression;

/**
 * The ScheduleEvent class.
 *
 * @since  __DEPLOY_VERSION__
 */
class ScheduleEvent
{
    /**
     * Property handler.
     *
     * @var callable
     */
    protected $handler;

    /**
     * Property expression.
     *
     * @var CronExpression
     */
    protected $expression;

    /**
     * Property tags.
     *
     * @var  array
     */
    protected $tags = [];

    /**
     * ScheduleEvent constructor.
     *
     * @param CronExpression|string $expression
     * @param callable              $handler
     */
    public function __construct($expression, callable $handler)
    {
        $this->handler = $handler;
        $this->setExpression($expression);
    }

    /**
     * execute
     *
     * @return  mixed
     *
     * @since  __DEPLOY_VERSION__
     */
    public function execute()
    {
        $handler = $this->handler;

        return $handler();
    }

    /**
     * isDue
     *
     * @param string $currentTime
     * @param string $timeZone
     *
     * @return  bool
     *
     * @since  __DEPLOY_VERSION__
     */
    public function isDue($currentTime = 'now', $timeZone = null): bool
    {
        return $this->expression->isDue($currentTime, $timeZone);
    }

    /**
     * tag
     *
     * @param array|string $tags
     *
     * @return  ScheduleEvent
     *
     * @since  __DEPLOY_VERSION__
     */
    public function tags($tags): self
    {
        $tags = (array) $tags;

        $tags = array_merge($this->tags, $tags);

        $this->tags = array_unique($tags);

        return $this;
    }

    /**
     * Method to get property Handler
     *
     * @return  callable
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getHandler(): callable
    {
        return $this->handler;
    }

    /**
     * Method to set property handler
     *
     * @param callable $handler
     *
     * @return  static  Return self to support chaining.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function setHandler(callable $handler)
    {
        $this->handler = $handler;

        return $this;
    }

    /**
     * Method to get property Expression
     *
     * @return  CronExpression
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getExpression(): CronExpression
    {
        return $this->expression;
    }

    /**
     * Method to set property expression
     *
     * @param CronExpression|string $expression
     *
     * @return  static  Return self to support chaining.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function setExpression($expression)
    {
        if (is_string($expression)) {
            $expression = CronExpression::factory($expression);
        }

        $this->expression = $expression;

        return $this;
    }

    /**
     * Method to get property Tags
     *
     * @return  array
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * Method to set property tags
     *
     * @param array $tags
     *
     * @return  static  Return self to support chaining.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function setTags(array $tags)
    {
        $this->tags = $tags;

        return $this;
    }
}
