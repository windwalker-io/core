<?php

declare(strict_types=1);

namespace Windwalker\Core\Http\Event;

use Windwalker\Core\Http\AppRequest;
use Windwalker\Event\BaseEvent;
use Windwalker\Utilities\Accessible\AccessorBCTrait;

/**
 * The RequestGetValueEvent class.
 */
class RequestGetValueEvent extends BaseEvent
{
    use AccessorBCTrait;

    public const string TYPE_BODY = 'body';

    public const string TYPE_QUERY = 'query';

    public const string TYPE_URL_VARS = 'url_vars';

    public function __construct(
        public AppRequest $appRequest,
        public string $type = self::TYPE_BODY,
        public array $values = [],
    ) {
    }

    /**
     * @return array
     *
     * @deprecated  Use property instead.
     */
    public function &getValues(): array
    {
        return $this->values;
    }
}
