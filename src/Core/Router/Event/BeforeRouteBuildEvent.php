<?php

declare(strict_types=1);

namespace Windwalker\Core\Router\Event;

use Windwalker\Core\Router\Navigator;
use Windwalker\Event\BaseEvent;
use Windwalker\Utilities\Accessible\AccessorBCTrait;

/**
 * The BeforeRouteBuildEvent class.
 */
class BeforeRouteBuildEvent extends BaseEvent
{
    use AccessorBCTrait;

    public function __construct(
        public string $route,
        public array $query,
        public Navigator $navigator,
        public int $options
    ) {
    }

    /**
     * @return string
     *
     * @deprecated  Use property instead.
     */
    public function &getRoute(): string
    {
        return $this->route;
    }

    /**
     * @return array
     *
     * @deprecated  Use property instead.
     */
    public function &getQuery(): array
    {
        return $this->query;
    }
}
