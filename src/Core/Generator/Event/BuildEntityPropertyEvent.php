<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Generator\Event;

use PhpParser\Node\Stmt\Property;
use Windwalker\Core\Generator\Builder\EntityMemberBuilder;
use Windwalker\Database\Schema\Ddl\Column;
use Windwalker\Event\AbstractEvent;

/**
 * The GenEntityPropertyEvent class.
 */
class BuildEntityPropertyEvent extends AbstractEvent
{
    protected string $propName = '';

    protected Property $prop;

    protected Column $column;

    protected EntityMemberBuilder $entityMemberBuilder;

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
     * @return Column
     */
    public function getColumn(): Column
    {
        return $this->column;
    }

    /**
     * @param  Column  $column
     *
     * @return  static  Return self to support chaining.
     */
    public function setColumn(Column $column): static
    {
        $this->column = $column;

        return $this;
    }
}
