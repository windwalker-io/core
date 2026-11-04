<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Database\Exporter\Stub;

use Psr\Http\Message\StreamInterface;

/**
 * Isolates CLI discovery and PHP row queries for exporter orchestration tests.
 * Remove with the multi-platform CLI priority regression tests.
 */
trait CliExporterHarness
{
    public bool $cliAvailable = true;

    public bool $gzipAvailable = true;

    public bool $phpFails = false;

    protected function findExecutable(string $name, string $environment): ?string
    {
        return !$this->cliAvailable || $name === 'expdp' || ($name === 'gzip' && !$this->gzipAvailable)
            ? null : $name;
    }

    protected function schemaObjects(): array
    {
        return ['SEQUENCE' => [], 'INDEX' => [], 'VIEW' => [], 'TRIGGER' => []];
    }

    protected function exportPhp(StreamInterface $stream): void
    {
        $stream->write('PHP SQL');
        if ($this->phpFails) {
            throw new \DomainException('Unsupported PHP fixture schema.');
        }
    }
}
