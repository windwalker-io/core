<?php

declare(strict_types=1);

namespace Windwalker\Core\Events\Web;

use Windwalker\Core\Application\Context\AppContextInterface;
use Windwalker\Event\BaseEvent;
use Windwalker\Utilities\Accessible\AccessorBCTrait;

/**
 * The AfterControllerDispatchEvent class.
 */
class AfterControllerDispatchEvent extends BaseEvent
{
    use AccessorBCTrait;

    /**
     * @param  mixed                $response
     * @param  AppContextInterface  $app
     */
    public function __construct(public AppContextInterface $app, public mixed $response)
    {
    }
}
