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
use PhpParser\ParserFactory;
use Unicorn\Field\CalendarField;
use Windwalker\Core\Language\LangService;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\Database\Manager\TableManager;
use Windwalker\Database\Schema\Ddl\Column;
use Windwalker\DI\Attributes\Inject;
use Windwalker\Form\Field\HiddenField;
use Windwalker\Form\Field\NumberField;
use Windwalker\Form\Field\TextareaField;
use Windwalker\Form\Field\TextField;
use Windwalker\Utilities\Str;
use Windwalker\Utilities\StrNormalize;

/**
 * The FormFieldsBuilder class.
 */
class FormFieldsBuilder extends AbstractAstBuilder
{
    protected array $uses = [];
    protected array $newUses = [];
    protected bool|string|null $langPrefix = null;

    /**
     * FormFieldsBuilder constructor.
     */
    public function __construct(protected string $className, protected TableManager $table)
    {
    }

    protected function getDb(): DatabaseAdapter
    {
        return $this->table->getDb();
    }

    public function process(array $options = [], ?array &$added = null): string
    {
        $added = [];
        $ref = new \ReflectionClass($this->getClassName());
        $this->langPrefix = $options['use-lang'] ?? null;

        $columns       = $this->table->getColumnNames(true);
        $existsColumns = [];
        $factory       = $this->createNodeFactory();
        $fields        = [];

        $leaveNode = function (Node $node) use ($ref, $factory, &$existsColumns, $columns, &$fields, &$added) {
            if ($node instanceof Node\Stmt\UseUse) {
                $this->uses[] = (string) $node->name;
            }

            if (
                $node instanceof Node\Expr\MethodCall
                && $node->name->name === 'add'
                && $node->args[0]->value instanceof Node\Scalar\String_
            ) {
                $existsColumns[] = $node->args[0]->value->value;
            }

            if (
                $node instanceof Node\Stmt\ClassMethod
                && $node->name->name === 'define'
            ) {
                $columns = array_diff($columns, $existsColumns);

                foreach ($columns as $column) {
                    $added[] = $column;
                    $fields[] = $this->createFieldDefine($column);
                }

                $node->stmts[] = new Node\Stmt\Expression(new Node\Scalar\String_('@replace-line'));
            }

            if ($node instanceof Node\Stmt\Namespace_) {
                $uses = array_unique($this->newUses);

                foreach ($uses as $use) {
                    array_unshift($node->stmts, $factory->use($use)->getNode());
                }
            }

            if ($node instanceof Node\Stmt\Class_) {
                if ($this->langPrefix && !$ref->hasProperty('lang')) {
                    $this->addUse(LangService::class);
                    $this->addUse(Inject::class);

                    $prop = $factory->property('lang')
                        ->setType('LangService')
                        ->makeProtected()
                        ->getNode();

                    $prop->attrGroups[] = $this->attributeGroup(
                        $this->attribute('Inject',)
                    );

                    array_unshift($node->stmts, $prop);
                }
            }
        };

        $newCode = $this->convertCode(
            file_get_contents($ref->getFileName()),
            null,
            $leaveNode
        );

        $addFields = implode("\n\n", $fields);
        return str_replace(
            "        '@replace-line';",
            $addFields,
            $newCode
        );
    }

    protected function createFieldDefine(string $columnName): string
    {
        $column = $this->table->getColumn($columnName);

        return $this->getFieldByColumnType($column);
    }

    protected function getFieldByColumnType(Column $column): string
    {
        $factory = $this->createNodeFactory();
        $colName = $column->getColumnName();

        if ($this->langPrefix) {
            $label = "\$this->lang->trans('" . $this->langPrefix . '.' . $colName . "')";
        } else {
            $label = Str::surrounds(
                StrNormalize::toSpaceSeparated(
                    StrNormalize::toPascalCase($column->getColumnName())
                ),
                "'"
            );
        }

        if ($column->isAutoIncrement()) {
            $this->addUse(HiddenField::class);
            return <<<PHP
        \$form->add('$colName', HiddenField::class);
PHP;
        }

        switch ($column->getDataType()) {
            case 'datetime':
            case 'timestamp':
                $this->addUse(CalendarField::class);

                return <<<PHP
        \$form->add('$colName', CalendarField::class)
            ->label($label);
PHP;

            case 'tinyint':
            case 'number':
            case 'integer':
            case 'int':
                $this->addUse(NumberField::class);

                return <<<PHP
        \$form->add('$colName', NumberField::class)
            ->label($label);
PHP;

            case 'float':
            case 'decimal':
                $this->addUse(NumberField::class);
                $precision = $column->getNumericPrecision();
                $step = 1;

                if ($precision > 0) {
                    $step = '0.' . (str_repeat('0', $precision - 1)) . '1';
                }

            return <<<PHP
        \$form->add('$colName', NumberField::class)
            ->label($label)
            ->step('$step');
PHP;

            case 'text':
            case 'mediumtext':
            case 'longtext':
                $this->addUse(TextareaField::class);

                return <<<PHP
        \$form->add('$colName', TextareaField::class)
            ->label($label)
            ->rows(7);
PHP;

            default:
                $this->addUse(TextField::class);

                return <<<PHP
        \$form->add('$colName', TextField::class)
            ->label($label);
PHP;
        }
    }

    protected function createParser(): \PhpParser\Parser
    {
        return (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    protected function addUse(string $ns): void
    {
        if (!in_array($ns, $this->uses, true)) {
            $this->newUses[] = $ns;
        }
    }
}
