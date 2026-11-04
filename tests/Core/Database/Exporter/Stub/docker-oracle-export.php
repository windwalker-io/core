<?php

declare(strict_types=1);

// Bridges client-side exp files from Docker to the host for live integration tests.
// Remove when LiveExporterIntegration no longer uses Docker-hosted Oracle tools.
require dirname(__DIR__, 5) . '/vendor/autoload.php';

use Symfony\Component\Process\Process;

$command = json_decode($argv[2], true, flags: JSON_THROW_ON_ERROR);
$remote = '/tmp/ww-export-' . bin2hex(random_bytes(12));
$file = null;
foreach ($command as &$argument) {
    if (str_starts_with($argument, 'FILE=')) {
        $file = substr($argument, 5);
        $argument = 'FILE=' . $remote . '.dmp';
    } elseif (str_starts_with($argument, 'LOG=')) {
        $argument = 'LOG=' . $remote . '.log';
    }
}
unset($argument);
if ($file === null) {
    throw new RuntimeException('Missing Oracle exp destination.');
}
$process = new Process(['docker', 'exec', '-i', $argv[1], ...$command], input: stream_get_contents(STDIN));
$process->setTimeout(120);
try {
    $process->run(static function (string $type, string $buffer): void {
        fwrite($type === Process::OUT ? STDOUT : STDERR, $buffer);
    });
    if ($process->isSuccessful()) {
        new Process(['docker', 'cp', $argv[1] . ':' . $remote . '.dmp', $file])->mustRun();
    }
} finally {
    new Process(['docker', 'exec', $argv[1], 'rm', '-f', $remote . '.dmp', $remote . '.log'])->mustRun();
}
exit($process->getExitCode() ?? 1);
