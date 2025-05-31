<?php

declare(strict_types=1);

namespace Windwalker\Core\Events\Web;

use Windwalker\Core\Application\Context\AppContextInterface;
use Windwalker\Event\BaseEvent;
use Windwalker\Utilities\Accessible\AccessorBCTrait;

/**
 * The BeforeControllerDispatchEvent class.
 */
class BeforeControllerDispatchEvent extends BaseEvent
{
    use AccessorBCTrait;

    public function __construct(
        public mixed $controller,
        public AppContextInterface $app,
    ) {
    }
}
