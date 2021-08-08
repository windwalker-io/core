<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Database\Exporter;

use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\Database\Platform\AbstractPlatform;

/**
 * The ExporterFactory class.
 */
class ExporterFactory
{
    public function createExporter(DatabaseAdapter $db, ApplicationInterface $app): AbstractExporter
    {
        $platform = $db->getPlatform();

        $class = match ($platform->getName()) {
            AbstractPlatform::MYSQL => MySQLExporter::class,
            default => throw new \DomainException(
                sprintf(
                    '%s exporter not yet prepared.',
                    $platform->getName()
                )
            )
        };

        return new $class($db, $app);
    }
}
