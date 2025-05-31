<?php

declare(strict_types=1);

namespace Windwalker\Core\Generator\Event;

use PhpParser\Node\Stmt\Property;
use Windwalker\Core\Generator\Builder\EntityMemberBuilder;
use Windwalker\Database\Schema\Ddl\Column;
use Windwalker\Event\BaseEvent;
use Windwalker\Utilities\Accessible\AccessorBCTrait;

/**
 * The BuildEntityPropertyEvent class.
 */
class BuildEntityPropertyEvent extends BaseEvent
{
    use AccessorBCTrait;

    public function __construct(
        public string $propName,
        public Property $prop,
        public Column $column,
        public EntityMemberBuilder $entityMemberBuilder
    ) {
    }
}
