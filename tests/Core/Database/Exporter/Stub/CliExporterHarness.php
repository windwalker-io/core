<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Database\Exporter\Stub;

use Psr\Http\Message\StreamInterface;

/**
 * Test double for exporter tests that exercise CLI selection without real tools.
 *
 * - cliAvailable / gzipAvailable: simulate missing exporter or gzip executables.
 * - phpFails: makes the PHP exporter throw, so error handling can be checked.
 * - schemaObjects() returns no extra objects; exportPhp() writes a fixed marker payload.
 *
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
