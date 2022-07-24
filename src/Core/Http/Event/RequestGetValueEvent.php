<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2022 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Http\Event;

use Windwalker\Core\Http\AppRequest;
use Windwalker\Event\AbstractEvent;

/**
 * The RequestGetValueEvent class.
 */
class RequestGetValueEvent extends AbstractEvent
{
    public const TYPE_BODY = 'body';
    public const TYPE_QUERY = 'query';
    public const TYPE_URL_VARS = 'url_vars';

    protected AppRequest $appRequest;

    protected array $values = [];

    protected string $type = self::TYPE_BODY;

    /**
     * @return AppRequest
     */
    public function getAppRequest(): AppRequest
    {
        return $this->appRequest;
    }

    /**
     * @param  AppRequest  $appRequest
     *
     * @return  static  Return self to support chaining.
     */
    public function setAppRequest(AppRequest $appRequest): static
    {
        $this->appRequest = $appRequest;

        return $this;
    }

    /**
     * @return array
     */
    public function &getValues(): array
    {
        return $this->values;
    }

    /**
     * @param  array  $values
     *
     * @return  static  Return self to support chaining.
     */
    public function setValues(array $values): static
    {
        $this->values = $values;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param  string  $type
     *
     * @return  static  Return self to support chaining.
     */
    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }
}
