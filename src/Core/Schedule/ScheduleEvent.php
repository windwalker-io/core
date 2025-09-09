<?php

declare(strict_types=1);

namespace Windwalker\Core\Schedule;

use Cron\CronExpression;
use DateTimeInterface;
use DateTimeZone;

use Windwalker\Core\DateTime\Clock;

use function Windwalker\nope;

/**
 * The ScheduleEvent class.
 *
 * @since  3.5.3
 */
class ScheduleEvent extends ScheduleExpression
{
    /**
     * Property handler.
     *
     * @var callable
     */
    protected $handler;

    /**
     * Property tags.
     *
     * @var  array
     */
    protected array $tags = [];

    protected \Closure $catch;

    /**
     * ScheduleEvent constructor.
     *
     * @param  string                 $name
     * @param  CronExpression|string  $expression
     * @param  callable|null          $handler
     */
    public function __construct(
        protected string $name,
        CronExpression|string $expression = '* * * * *',
        ?callable $handler = null
    ) {
        $this->handler = $handler;

        parent::__construct($expression);
    }

    /**
     * execute
     *
     * @return  mixed
     *
     * @since  3.5.3
     */
    public function execute(): mixed
    {
        $handler = $this->handler;

        return $handler();
    }

    /**
     * isDue
     *
     * @param  mixed|null                $clock
     * @param  DateTimeZone|string|null  $timeZone
     *
     * @return  bool
     *
     * @since  3.5.3
     */
    public function isDue(
        mixed $clock = null,
        DateTimeZone|string|null $timeZone = null
    ): bool {
        $clock = Clock::from($clock);

        if ($timeZone instanceof DateTimeZone) {
            $timeZone = $timeZone->getName();
        }

        return $this->expression->isDue($clock->now(), $timeZone);
    }

    /**
     * tag
     *
     * @param  array|string  $tags
     *
     * @return  ScheduleEvent
     *
     * @since  3.5.3
     */
    public function tags(string ...$tags): self
    {
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
        return $this->handler ?? nope();
    }

    /**
     * Method to set property handler
     *
     * @param  callable  $handler
     *
     * @return  static  Return self to support chaining.
     *
     * @since  3.5.3
     */
    public function handler(callable $handler): static
    {
        $this->handler = $handler;

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
     * @param  array  $tags
     *
     * @return  static  Return self to support chaining.
     *
     * @since  3.5.3
     */
    public function setTags(array $tags): static
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
     * @param  string  $name
     *
     * @return  static  Return self to support chaining.
     *
     * @since  3.5.6
     */
    public function name(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCatch(): ?\Closure
    {
        return $this->catch ?? null;
    }

    public function catch(\Closure $catch): static
    {
        $this->catch = $catch;

        return $this;
    }
}
