<?php
/**
 * Part of cli project.
 *
 * @copyright  Copyright (C) 2019 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

// phpcs:disable

use Asika\SimpleConsole\Console;

include_once __DIR__ . '/Console.php';

class Build extends Console
{
    /**
     * Property help.
     *
     * @var  string
     */
    protected $help = <<<HELP
[Usage] php release.php <version> [--options]

[Options]
    h | help   Show help information
    type       Version plus type, major, minor, patch, variant.
    v          Show more debug information.
    --dry-run  Dry run without git push or commit.
HELP;

    /**
     * doExecute
     *
     * @return  bool|mixed
     *
     * @since  __DEPLOY_VERSION__
     */
    protected function doExecute()
    {
        $currentVersion = trim(file_get_contents(__DIR__ . '/../VERSION'));
        $targetVersion = $this->getArgument(0);

        if (!$targetVersion) {
            $targetVersion = static::versionPlus($currentVersion, $this->getOption('type', 'patch'));
        }

        $this->out('Release version: ' . $targetVersion);

        static::writeVersion($targetVersion);
        $this->replaceDocblockTags($targetVersion);

        $this->exec(sprintf('git commit -am "Release version: %s"', $targetVersion));
        $this->exec(sprintf('git tag %s', $targetVersion));

        $this->exec('git push');
        $this->exec('git push --tags');

        return true;
    }

    /**
     * writeVersion
     *
     * @param string $version
     *
     * @return  bool|int
     *
     * @since  __DEPLOY_VERSION__
     */
    protected static function writeVersion(string $version)
    {
        return file_put_contents(static::versionFile(), $version . "\n");
    }

    /**
     * versionFile
     *
     * @return  string
     *
     * @since  __DEPLOY_VERSION__
     */
    protected static function versionFile(): string
    {
        return __DIR__ . '/../VERSION';
    }

    /**
     * versionPlus
     *
     * @param string $version
     * @param string $element
     * @param string $suffix
     *
     * @return  string
     *
     * @since  __DEPLOY_VERSION__
     */
    protected static function versionPlus(string $version, string $element = 'patch', string $suffix = ''): string
    {
        [$version] = explode('-', $version, 2);

        $numbers = array_pad(explode('.', $version), 3, 0);

        $numbers = array_combine(
            [
                'major',
                'minor',
                'patch',
                'variant'
            ],
            $numbers
        );

        if (!isset($numbers[$element])) {
            throw new \RuntimeException('Version type: ' . $element . ' not allow.');
        }

        $num =& $numbers[$element];
        $num++;

        $i = array_search($element, array_keys($numbers), true);

        foreach (range($i + 1, count($numbers) - 1) as $k) {
            $numbers[array_keys($numbers)[$k]] = 0;
        }

        if ($numbers['variant'] === 0) {
            unset($numbers['variant']);
            
            if ($numbers['patch'] === 0) {
                unset($numbers['patch']);
            }
        }

        $version = implode('.', $numbers);

        if ($suffix) {
            $version .= '-' . $suffix;
        }

        return $version;
    }

    /**
     * replaceDocblockTags
     *
     * @param string $version
     *
     * @return  void
     */
    protected function replaceDocblockTags(string $version): void
    {
        $this->out('Replacing Docblock...');

        $files = new RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                __DIR__ . '/../src',
                \FilesystemIterator::SKIP_DOTS
            )
        );

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());

            $content = str_replace(
                ['{DEPLOY_VERSION}', '__DEPLOY_VERSION__', '__LICENSE__', '${ORGANIZATION}', '{ORGANIZATION}'],
                [$version, $version, 'LGPL-2.0-or-later', 'LYRASOFT', 'LYRASOFT'],
                $content
            );

            file_put_contents($file->getPathname(), $content);
        }

        $this->exec('git checkout master');
        $this->exec(sprintf('git commit -am "Prepare for %s release."', $version));
        $this->exec('git push origin master');
    }

    /**
     * exec
     *
     * @param   string $command
     *
     * @return  static
     */
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
