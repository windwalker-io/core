<?php

declare(strict_types=1);

namespace Windwalker\Core\Migration;

use Windwalker\Database\DatabaseAdapter;
use Windwalker\Database\Manager\TableManager;
use Windwalker\Database\Schema\Ddl\Column;
use Windwalker\Database\Schema\Ddl\Constraint;
use Windwalker\Database\Schema\Ddl\Index;
use Windwalker\Utilities\Arr;

use function Windwalker\collect;

class MigrationSquashBuilder
{
    protected array $uses = [];

    public function __construct(protected DatabaseAdapter $db)
    {
    }

    /**
     * @param  TableManager  $tableManager
     *
     * @return  string[]
     */
    public function build(TableManager $tableManager): array
    {
        $tableName = $tableManager->getName();
        $columns = $tableManager->getColumns(true);
        $indexes = $tableManager->getIndexes();
        $constraints = $tableManager->getConstraints();

        $this->syncKeyColumns($indexes, $columns);
        $this->syncKeyColumns($constraints, $columns);

        $schemaLines = collect();

        foreach ($columns as $column) {
            $schemaLines[] = $this->buildColumnCode($column);
        }

        $schemaLines[] = '';

        foreach ($constraints as $constraint) {
            $schemaLines[] = $this->buildConstraintCode($constraint);
        }

        foreach ($indexes as $index) {
            if ($this->isColumnsSame($index, $constraints)) {
                continue;
            }

            $schemaLines[] = $this->buildIndexCode($index);
        }

        $schemaCode = $schemaLines->filter(fn($line) => $line !== null)
            ->map(
                function ($line) {
                    if ((string) $line === '') {
                        return '';
                    }

                    return str_repeat('    ', 4) . $line;
                }
            )
            ->implode("\n")
            ->trim();

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

    protected function buildColumnCode(Column $column): string
    {
        $segments = [
            '$schema',
        ];

        // $dataTypeHelper = $this->db->getPlatform()->getDataType();

        $type = $column->getDataType();
        $name = $column->columnName;
        $method = static::typeToMethod($type);

        if ($column->isPrimary() && $column->isAutoIncrement()) {
            if (($type === 'int' || $type === 'integer')) {
                $method = "primary";
            } elseif ($type === 'bigint') {
                $method = "bigintPrimary";
            }
        }

        $length = $column->getErratas()['custom_length'] ?? $column->getLengthExpression();

        if ($type === 'tinyint' && $length === 1) {
            $method = 'bool';
        } elseif ($column->getErratas()['is_json'] ?? false) {
            $method = 'json';
        }

        $segments[] = "$method('$name')";

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

        $def = $column->getColumnDefault();

        if ($def === null) {
            $segments[] = 'defaultValue(null)';
        } elseif (is_numeric($def)) {
            $segments[] = "defaultValue($def)";
        } elseif (is_string($def)) {
            $segments[] = "defaultValue('$def')";
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
            $isAI = $constraint->columnsCount() === 1 && $constraint->getFirstColumn()->isAutoIncrement();

            if ($isAI) {
                return null;
            }

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
        if (!($column->getErratas()['custom_length'] ?? null)) {
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
     * @param  array<Constraint|Index>  $keys
     * @param  array<Column>            $realColumns
     *
     * @return  void
     */
    protected function syncKeyColumns(array $keys, array $realColumns): void
    {
        foreach ($keys as $key) {
            foreach ($key->columns as $column) {
                $key->columns[$column->columnName] = $realColumn = $realColumns[$column->columnName];

                if ($key instanceof Constraint && $key->isPrimary()) {
                    $realColumn->primary(true);
                }
            }
        }
    }

    /**
     * @param  Index              $index
     * @param  array<Constraint>  $constraints
     *
     * @return  bool
     */
    protected function isColumnsSame(Index $index, array $constraints): bool
    {
        foreach ($constraints as $constraint) {
            if (!$constraint->isUnique() && !$constraint->isPrimary()) {
                continue;
            }

            $uniqCols = array_column($constraint->columns, 'columnName');
            $idxCols = array_column($index->columns, 'columnName');

            if (Arr::arrayEquals($uniqCols, $idxCols)) {
                return true;
            }
        }

        return false;
    }

    public static function buildMigrationTemplate(
        string $name,
        string $createTableCode,
        string $dropTableCode,
        string $version
    ): string {
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
    function () use (\$mig) {
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

    public static function buildSquashMigrationCode(string $name, string $version, array $versions): string
    {
        $squashCode = static::buildSquashActionCode($versions);

        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Migration;

use Windwalker\Core\Console\ConsoleApplication;
use Windwalker\Core\Migration\Migration;

/**
 * Migration UP: {$version}_{$name}.
 *
 * @var Migration \$mig
 * @var ConsoleApplication \$app
 */
\$mig->up(
    function () {
        $squashCode
    }
);

PHP;
    }

    /**
     * @param  array  $versions
     *
     * @return  string
     */
    public static function buildSquashActionCode(array $versions): string
    {
        $versionsCode = (string) collect($versions)
            ->map(fn($v) => "'$v'")
            ->implode(",\n" . str_repeat('    ', 4));

        if (!trim($versionsCode)) {
            $versionsCode = '//';
        }

        return <<<PHP
        /** @var \Windwalker\Core\Migration\MigrationService \$this */
        \$this->squashIfNotFresh(
            ignoreVersions: [
                $versionsCode
            ]
        );
PHP;
    }
}
