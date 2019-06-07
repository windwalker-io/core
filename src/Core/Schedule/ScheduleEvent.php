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
 * @since  3.5.3
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
     * Property name.
     *
     * @var string
     */
    protected $name = '';

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
     * @since  3.5.3
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
     * @since  3.5.3
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
     * @since  3.5.3
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
     * @since  3.5.3
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
     * @since  3.5.3
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
     * @since  3.5.3
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
     * @since  3.5.3
     */
    public function setExpression($expression)
    {
        if ($expression === '@always') {
            $expression = new AlwaysExpression($expression);
        } elseif (is_string($expression)) {
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
     * @since  3.5.3
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
     * @since  3.5.3
     */
    public function setTags(array $tags)
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Method to get property Name
     *
     * @return  string
     *
     * @since  3.5.6
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Method to set property name
     *
     * @param string $name
     *
     * @return  static  Return self to support chaining.
     *
     * @since  3.5.6
     */
    public function name(string $name)
    {
        $this->name = $name;

        return $this;
    }
}
