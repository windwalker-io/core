<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Generator\Event;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use Windwalker\Core\Generator\Builder\EntityMemberBuilder;
use Windwalker\Database\Schema\Ddl\Column;
use Windwalker\Event\AbstractEvent;

/**
 * The BuildEntityMethodEvent class.
 */
class BuildEntityMethodEvent extends AbstractEvent
{
    protected string $accessorType;

    protected string $methodName = '';

    protected string $propName = '';

    protected Node\Stmt\Property $prop;

    protected ?Column $column;

    protected ClassMethod $method;

    protected Node $type;

    protected EntityMemberBuilder $entityMemberBuilder;

    /**
     * @return ClassMethod
     */
    public function getMethod(): ClassMethod
    {
        return $this->method;
    }

    /**
     * @param  ClassMethod  $method
     *
     * @return  static  Return self to support chaining.
     */
    public function setMethod(ClassMethod $method): static
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @return EntityMemberBuilder
     */
    public function getEntityMemberBuilder(): EntityMemberBuilder
    {
        return $this->entityMemberBuilder;
    }

    /**
     * @param  EntityMemberBuilder  $entityMemberBuilder
     *
     * @return  static  Return self to support chaining.
     */
    public function setEntityMemberBuilder(EntityMemberBuilder $entityMemberBuilder): static
    {
        $this->entityMemberBuilder = $entityMemberBuilder;

        return $this;
    }

    /**
     * @return string
     */
    public function getMethodName(): string
    {
        return $this->methodName;
    }

    /**
     * @param  string  $methodName
     *
     * @return  static  Return self to support chaining.
     */
    public function setMethodName(string $methodName): static
    {
        $this->methodName = $methodName;

        return $this;
    }

    /**
     * @return string
     */
    public function getPropName(): string
    {
        return $this->propName;
    }

    /**
     * @param  string  $propName
     *
     * @return  static  Return self to support chaining.
     */
    public function setPropName(string $propName): static
    {
        $this->propName = $propName;

        return $this;
    }

    /**
     * @return Node
     */
    public function getType(): Node
    {
        return $this->type;
    }

    /**
     * @param  Node  $type
     *
     * @return  static  Return self to support chaining.
     */
    public function setType(Node $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getAccessorType(): string
    {
        return $this->accessorType;
    }

    /**
     * @param  string  $accessorType
     *
     * @return  static  Return self to support chaining.
     */
    public function setAccessorType(string $accessorType): static
    {
        $this->accessorType = $accessorType;

        return $this;
    }

    public function isGetter(): bool
    {
        return $this->accessorType === 'getter';
    }

    public function isSetter(): bool
    {
        return $this->accessorType === 'setter';
    }

    /**
     * @return Property
     */
    public function getProp(): Property
    {
        return $this->prop;
    }

    /**
     * @param  Property  $prop
     *
     * @return  static  Return self to support chaining.
     */
    public function setProp(Property $prop): static
    {
        $this->prop = $prop;

        return $this;
    }

    /**
     * @return ?Column
     */
    public function getColumn(): ?Column
    {
        return $this->column;
    }

    /**
     * @param  ?Column  $column
     *
     * @return  static  Return self to support chaining.
     */
    public function setColumn(?Column $column): static
    {
        $this->column = $column;

        return $this;
    }

    public function getTypeName(): string
    {
        $type = $this->type;

        if ($type instanceof Node\NullableType) {
            $type = $type->type;
        }

        return (string) $type;
    }
}
