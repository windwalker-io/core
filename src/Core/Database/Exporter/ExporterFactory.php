<?php

declare(strict_types=1);

namespace Windwalker\Core\Database\Exporter;

use DomainException;
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
            default => throw new DomainException(
                sprintf(
                    '%s exporter not yet prepared.',
                    $platform->getName()
                )
            )
        };

        return new $class($db, $app);
    }
}
