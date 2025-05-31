<?php

declare(strict_types=1);

namespace Windwalker\Core\Generator\Event;

use PhpParser\Node;
use Windwalker\Core\Generator\Builder\EntityMemberBuilder;
use Windwalker\Database\Schema\Ddl\Column;
use Windwalker\Event\BaseEvent;

class BuildEntityHookEvent extends BaseEvent
{
    public bool $isGet {
        get => $this->hookType === \PropertyHookType::Get;
    }

    public bool $isSet {
        get => $this->hookType === \PropertyHookType::Set;
    }

    public function __construct(
        public \PropertyHookType $hookType,
        public string $propName,
        public Node\Stmt\Property $prop,
        public ?Column $column,
        public Node $type,
        public EntityMemberBuilder $entityMemberBuilder,
        public ?Node\PropertyHook $hook = null,
    ) {
    }
}
