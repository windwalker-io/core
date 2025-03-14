<?php

declare(strict_types=1);

namespace Windwalker\Core\Migration;

use Windwalker\Core\Generator\Builder\AbstractAstBuilder;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\Database\Manager\TableManager;
use Windwalker\Database\Schema\Ddl\Column;
use Windwalker\Database\Schema\Ddl\Constraint;
use Windwalker\Database\Schema\Ddl\Index;

use function Windwalker\collect;

class MigrationSquashBuilder extends AbstractAstBuilder
{
    protected array $uses = [];

    public function __construct(protected DatabaseAdapter $db, protected TableManager $tableManager)
    {
    }

    public function process(array $options = []): string
    {
        $version = $options['version'];
        $name = $options['name'];

        [$upCode, $downCode] = $this->buildMigrateCode($this->tableManager);

        return static::buildMigrationTemplate(
            $name,
            $upCode,
            $downCode,
            $version,
        );
    }

    protected function buildColumnCode(Column $column): string
    {
        $segments = [
            '$schema',
        ];

        // $dataTypeHelper = $this->db->getPlatform()->getDataType();

        $type = $column->getDataType();
        $name = $column->columnName;
        $method = static::typeToMethod($type);

        // if ($column->isPrimary()) {
        //     if ($column->isAutoIncrement()) {
        //         if (($type === 'int' || $type === 'integer')) {
        //             $segments[] = "primary('$name')";
        //         } elseif ($type === 'string') {
        //             $segments[] = "bigintPrimary('$name')";
        //         } else {
        //             $method = static::typeToMethod($type);
        //             $segments[] = "$method('$name')";
        //             $segments[] = "primary(true)";
        //             $segments[] = "autoIncrement(true)";
        //         }
        //     } elseif ($type === 'varchar') {
        //         $se
        //     }
        // }

        $length = $column->getErratas()['custom_length'] ?? $column->getLengthExpression();

        if ($type === 'tinyint' && $length === 1) {
            $method = 'bool';
        } elseif ($column->getErratas()['is_json'] ?? false) {
            $method = 'json';
        }

        $segments[] = "$method('$name')";

        if ($column->isAutoIncrement()) {
            $segments[] = 'autoIncrement(true)';
        }

        if ($length && !static::ignoreLength($column)) {
            if (!str_contains((string) $length, ',')) {
                $length = (int) $length;
                $segments[] = "length($length)";
            } else {
                $segments[] = "length('$length')";
            }
        }

        if ($column->getIsNullable()) {
            $segments[] = 'nullable(true)';
        }

        if ($comment = $column->getComment()) {
            $segments[] = "comment('$comment')";
        }

        return implode('->', $segments) . ';';
    }

    protected function buildConstraintCode(Constraint $constraint): ?string
    {
        $segments = [
            '$schema',
        ];

        $names = (string) collect($constraint->getColumnNames())
            ->map(fn($name) => "'$name'")
            ->implode(', ');

        if ($constraint->isPrimary()) {
            if ($constraint->columnsCount() > 1) {
                $segments[] = "addPrimaryKey([$names])";
            } else {
                $segments[] = "addPrimaryKey($names)";
            }
        } elseif ($constraint->isUnique()) {
            if ($constraint->columnsCount() > 1) {
                $segments[] = "addUniqueKey([$names])";
            } else {
                $segments[] = "addUniqueKey($names)";
            }
        } else {
            trigger_error(
                'Currently does not supports constraints CHECK or FOREIGN KEY',
                E_USER_WARNING
            );

            return null;
        }

        return implode('->', $segments) . ';';
    }

    protected function buildIndexCode(Index $index)
    {
        $segments = [
            '$schema',
        ];

        $names = (string) collect($index->getColumnNames())
            ->map(fn($name) => "'$name'")
            ->implode(', ');

        if ($index->columnsCount() > 1) {
            $segments[] = "addIndex([$names])";
        } else {
            $segments[] = "addIndex($names)";
        }

        return implode('->', $segments) . ';';
    }

    protected static function typeToMethod(string $type): string
    {
        return match ($type) {
            'int', => 'integer',
            'varchar' => 'varchar',
            default => $type,
        };
    }

    protected static function ignoreLength(Column $column): bool
    {
        if ($column->getErratas()['custom_length'] ?? null) {
            return true;
        }

        if (
            in_array($column->getDataType(), ['integer', 'int'])
            && in_array((int) $column->getLengthExpression(), [10, 11])
        ) {
            return true;
        }

        if (
            in_array($column->getDataType(), ['tinyint', 'int'])
            && in_array((int) $column->getLengthExpression(), [2, 3])
        ) {
            return true;
        }

        if (
            in_array($column->getDataType(), ['varchar', 'char'])
            && ((int) $column->getLengthExpression()) === 255
        ) {
            return true;
        }

        if (
            in_array($column->getDataType(), ['text', 'longtext'])
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param  array<Constraint>  $constraints
     *
     * @return  string[]
     */
    protected function findPrimaryKeys(array $constraints): array
    {
        $keys = [];

        foreach ($constraints as $constraint) {
            if ($constraint->constraintType === 'PRIMARY KEY') {
                foreach ($constraint->columns as $column) {
                    $keys[] = $column->columnName;
                }

                return $keys;
            }
        }

        return $keys;
    }

    protected function findUniqueKeys(array $constraints): array
    {
        $keys = [];

        foreach ($constraints as $constraint) {
            if ($constraint->constraintType === 'UNIQUE') {
                foreach ($constraint->columns as $column) {
                    $keys[] = $column->columnName;
                }
            }
        }

        return $keys;
    }

    /**
     * @param  TableManager  $tableManager
     *
     * @return  string[]
     */
    public function buildMigrateCode(TableManager $tableManager): array
    {
        $tableName = $tableManager->getName();
        $columns = $tableManager->getColumns(true);
        $indexes = $tableManager->getIndexes();
        $constraints = $tableManager->getConstraints();

        $pks = $this->findPrimaryKeys($constraints);
        $uks = $this->findUniqueKeys($constraints);

        $schemaLines = collect();

        foreach ($columns as $column) {
            if (in_array($column->columnName, $pks, true)) {
                $column->primary(true);
            }

            $schemaLines[] = $this->buildColumnCode($column);
        }

        $schemaLines[] = '';

        foreach ($constraints as $constraint) {
            $schemaLines[] = $this->buildConstraintCode($constraint);
        }

        foreach ($indexes as $index) {
            if ($index->columnsCount() === 1) {
                $colName = $index->getFirstColumn()->columnName;

                if (in_array($colName, $pks, true) || in_array($colName, $uks, true)) {
                    continue;
                }
            }

            $schemaLines[] = $this->buildIndexCode($index);
        }

        $schemaCode = $schemaLines->filter(fn($line) => $line !== null)
            ->map(
                function ($line) {
                    if ((string) $line === '') {
                        return '';
                    }

                    return '            ' . $line;
                }
            )
            ->implode(', ');

        $upCode = <<<PHP
        \$mig->createTable(
            '$tableName',
            function (Schema \$schema) {
                $schemaCode
            }
        );
PHP;

        $downCode = <<<PHP
        \$mig->dropTables('$tableName');
PHP;

        return [$upCode, $downCode];
    }

    protected static function buildMigrationTemplate(
        string $name,
        string $createTableCode,
        string $dropTableCode,
        string $version
    ) {
        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Migration;

use Windwalker\Core\Console\ConsoleApplication;
use Windwalker\Core\Migration\Migration;
use Windwalker\Database\Schema\Schema;

/**
 * Migration UP: {$version}_{$name}.
 *
 * @var Migration \$mig
 * @var ConsoleApplication \$app
 */
\$mig->up(
    static function () use (\$mig) {
$createTableCode
    }
);

/**
 * Migration DOWN.
 */
\$mig->down(
    static function () use (\$mig) {
$dropTableCode
    }
);

PHP;
    }
}
