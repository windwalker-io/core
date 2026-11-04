<?php

declare(strict_types=1);

// Bridges the real container CLI to a host artifact for opt-in integration tests.
// Remove when LiveExporterIntegration no longer uses Docker-hosted pg_dump.
require dirname(__DIR__, 5) . '/vendor/autoload.php';

use Symfony\Component\Process\Process;

$command = json_decode($argv[2], true, flags: JSON_THROW_ON_ERROR);
$file = null;
foreach ($command as $key => $argument) {
    if (str_starts_with($argument, '--file=')) {
        $file = substr($argument, 7);
        unset($command[$key]);
    }
}
if ($file === null) {
    throw new RuntimeException('Missing pg_dump destination.');
}
$output = fopen($file, 'wb');
try {
    $process = new Process(['docker', 'exec', '-e', 'PGPASSWORD', $argv[1], ...$command]);
    $process->setTimeout(120);
    $process->run(static function (string $type, string $buffer) use ($output): void {
        if ($type === Process::OUT) {
            fwrite($output, $buffer);
        } else {
            fwrite(STDERR, $buffer);
        }
    });
} finally {
    fclose($output);
}
exit($process->getExitCode() ?? 1);
