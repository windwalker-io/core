<?php

declare(strict_types=1);

namespace Windwalker\Core\Generator\Event;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use Windwalker\Core\Generator\Builder\EntityMemberBuilder;
use Windwalker\Database\Schema\Ddl\Column;
use Windwalker\Event\BaseEvent;
use Windwalker\Utilities\Accessible\AccessorBCTrait;

/**
 * The BuildEntityMethodEvent class.
 */
class BuildEntityMethodEvent extends BaseEvent
{
    use AccessorBCTrait;

    public bool $isGetter {
        get => $this->accessorType === 'getter';
    }

    public bool $isSetter {
        get => $this->accessorType === 'setter';
    }

    public string $typeName {
        get => $this->getTypeName();
    }

    public function __construct(
        public string $accessorType,
        public string $methodName,
        public string $propName,
        public Node\Stmt\Property $prop,
        public ?Column $column,
        public ClassMethod $method,
        public Node $type,
        public EntityMemberBuilder $entityMemberBuilder,
    ) {
    }

    /**
     * @return  bool
     *
     * @deprecated
     */
    public function isGetter(): bool
    {
        return $this->accessorType === 'getter';
    }

    /**
     * @return  bool
     *
     * @deprecated
     */
    public function isSetter(): bool
    {
        return $this->accessorType === 'setter';
    }

    /**
     * @return  string
     *
     * @deprecated
     */
    public function getTypeName(): string
    {
        $type = $this->type;

        if ($type instanceof Node\NullableType) {
            $type = $type->type;
        }

        if ($type instanceof Node\UnionType) {
            $typeNames = [];

            foreach ($type->types as $type) {
                $typeNames[] = (string) $type;
            }

            return implode('|', $typeNames);
        }

        return (string) $type;
    }
}
