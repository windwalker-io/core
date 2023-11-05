<?php

declare(strict_types=1);

namespace Windwalker\Core\Generator\Builder;

use Closure;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * The CallbackAstBuilder class.
 */
class CallbackAstBuilder extends AbstractAstBuilder
{
    protected ?Closure $beforeTraverse = null;

    protected ?Closure $afterTraverse = null;

    /**
     * CallbackAstBuilder constructor.
     */
    public function __construct(
        protected ?string $code = null,
        protected ?Closure $enterNode = null,
        protected ?Closure $leaveNode = null
    ) {
        //
    }

    public function process(array $options = []): string
    {
        return $this->convertCode(
            $this->code,
            $this->enterNode,
            $this->leaveNode
        );
    }

    /**
     * @return Closure|null
     */
    public function getEnterNode(): ?Closure
    {
        return $this->enterNode;
    }

    /**
     * @param  Closure|null  $enterNode
     *
     * @return  static  Return self to support chaining.
     */
    public function enterNode(?Closure $enterNode): static
    {
        $this->enterNode = $enterNode;

        return $this;
    }

    /**
     * @return Closure|null
     */
    public function getLeaveNode(): ?Closure
    {
        return $this->leaveNode;
    }

    /**
     * @param  Closure|null  $leaveNode
     *
     * @return  static  Return self to support chaining.
     */
    public function leaveNode(?Closure $leaveNode): static
    {
        $this->leaveNode = $leaveNode;

        return $this;
    }

    /**
     * @return Closure|null
     */
    public function getBeforeTraverse(): ?Closure
    {
        return $this->beforeTraverse;
    }

    /**
     * @param  Closure|null  $beforeTraverse
     *
     * @return  static  Return self to support chaining.
     */
    public function beforeTraverse(?Closure $beforeTraverse): static
    {
        $this->beforeTraverse = $beforeTraverse;

        return $this;
    }

    /**
     * @return Closure|null
     */
    public function getAfterTraverse(): ?Closure
    {
        return $this->afterTraverse;
    }

    /**
     * @param  Closure|null  $afterTraverse
     *
     * @return  static  Return self to support chaining.
     */
    public function afterTraverse(?Closure $afterTraverse): static
    {
        $this->afterTraverse = $afterTraverse;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * @param  string|null  $code
     *
     * @return  static  Return self to support chaining.
     */
    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    protected function createVisitor(?Closure $enterNode, ?Closure $leaveNode): NodeVisitorAbstract
    {
        return new class ($enterNode, $leaveNode) extends NodeVisitorAbstract {
            public function __construct(
                protected ?Closure $enterNode = null,
                protected ?Closure $leaveNode = null,
                protected ?Closure $beforeTraverse = null,
                protected ?Closure $afterTraverse = null,
            ) {
                //
            }

            public function beforeTraverse(array $nodes): mixed
            {
                if (!$this->beforeTraverse) {
                    return null;
                }

                return ($this->beforeTraverse)($nodes, $this);
            }

            public function afterTraverse(array $nodes): mixed
            {
                if (!$this->afterTraverse) {
                    return null;
                }

                return ($this->afterTraverse)($nodes, $this);
            }

            public function enterNode(Node $node): mixed
            {
                if (!$this->enterNode) {
                    return null;
                }

                return ($this->enterNode)($node, $this);
            }

            public function leaveNode(Node $node): mixed
            {
                if (!$this->leaveNode) {
                    return null;
                }

                return ($this->leaveNode)($node, $this);
            }
        };
    }
}
