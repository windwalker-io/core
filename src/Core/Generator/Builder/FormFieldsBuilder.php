<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Generator\Builder;

use PhpParser\Node;
use Windwalker\ORM\Metadata\EntityMetadata;

/**
 * The FormFieldsBuilder class.
 */
class FormFieldsBuilder extends AbstractAstBuilder
{
    /**
     * FormFieldsBuilder constructor.
     */
    public function __construct(protected string $className, protected EntityMetadata $metadata)
    {
    }

    public function process(array $options = []): string
    {
        $ref = new \ReflectionClass($this->getClassName());

        $enterNode = function (Node $node) {

        };

        return $this->convertCode(
            file_get_contents($ref->getFileName()),
            $enterNode
        );
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }
}
