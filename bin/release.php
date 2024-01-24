<?php

// phpcs:disable

use Asika\SimpleConsole\Console;

include_once __DIR__ . '/Console.php';

class Build extends Console
{
    protected $help = <<<HELP
[Usage] php release.php <version>

[Arguments]
    version    major|minor|patch or direct a version string, keep empty will be "patch".

[Options]
    h | help   Show help information
    v          Show more debug information.
    --dry-run  Dry run without git push or commit.
HELP;

    protected array $scripts = [];

    protected function doExecute(): true
    {
        foreach ($this->scripts as $script) {
            $this->exec($script);
        }

        $currentVersion = trim(file_get_contents(__DIR__ . '/../VERSION'));
        $targetVersion = (string) $this->getArgument(0);

        $targetVersion = static::versionPlus($currentVersion, $targetVersion);

        $this->out('Release version: ' . $targetVersion);

        static::writeVersion($targetVersion);

        $this->exec(sprintf('git commit -am "Release version: %s"', $targetVersion));
        $this->exec(sprintf('git tag %s', $targetVersion));

        $this->exec('git push');
        $this->exec('git push --tags');

        return true;
    }

    protected static function writeVersion(string $version)
    {
        return file_put_contents(static::versionFile(), $version . "\n");
    }

    protected static function versionFile(): string
    {
        return __DIR__ . '/../VERSION';
    }

    protected static function versionPlus(
        string $currentVersion,
        string $targetVersion,
        string $suffix = ''
    ): string {
        [$currentVersion] = explode('-', $currentVersion, 2) + ['', ''];

        [$major, $minor, $patch] = explode('.', $currentVersion, 3) + ['', '0', '0'];
        $major = (int) $major;
        $minor = (int) $minor;
        $patch = (int) $patch;

        switch ($targetVersion) {
            case 'major':
                $major++;
                $minor = $patch = 0;
                break;

            case 'minor':
                $minor++;
                $patch = 0;
                break;

            case 'patch':
            case '':
                $patch++;
                break;

            default:
                return $targetVersion . '-' . $suffix;
        }

        $currentVersion = $major . '.' . $minor . '.' . $patch;

        if ($suffix) {
            $currentVersion .= '-' . $suffix;
        }

        return $currentVersion;
    }

    public function addScript(string $script): static
    {
        $this->scripts[] = $script;

        return $this;
    }

    protected function exec($command)
    {
        $this->out('>> ' . $command);

        if (!$this->getOption('dry-run')) {
            system($command);
        }

        return $this;
    }
}

exit((new Build())->execute());
