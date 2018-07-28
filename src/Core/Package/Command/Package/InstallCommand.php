<?php
/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Package\Command\Package;

use Windwalker\Console\Prompter\BooleanPrompter;
use Windwalker\Console\Prompter\TextPrompter;
use Windwalker\Core\Console\ConsoleHelper;
use Windwalker\Core\Console\CoreCommand;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Filesystem\File;
use Windwalker\Filesystem\Folder;

/**
 * The InstallCommand class.
 *
 * phpcs:disable -- Too many CS errors.
 *
 * @since  3.0
 */
class InstallCommand extends CoreCommand
{
    /**
     * Property name.
     *
     * @var  string
     */
    protected $name = 'install';

    /**
     * Property description.
     *
     * @var  string
     */
    protected $description = 'Install package';

    /**
     * Initialise command.
     *
     * @return void
     *
     * @since  2.0
     */
    protected function init()
    {
        $this->addOption('hard')
            ->description('Hard copy assets.')
            ->defaultValue(false);
    }

    /**
     * Execute this command.
     *
     * @return int
     *
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     * @since  2.0
     */
    protected function doExecute()
    {
        $env      = $this->getOption('env');
        $resolver = ConsoleHelper::getAllPackagesResolver($env, $this->console);
        $names    = $this->io->getArguments();

        if (!$names) {
            throw new \InvalidArgumentException('No package input.');
        }

        foreach ($names as $name) {
            $this->out()
                ->out('Start installing package: <info>' . $name . '</info>')
                ->out('---------------------------');

            $package = $resolver->getPackage($name);

            if (!$package) {
                $this->err('<comment>Package: ' . $name . ' not found.</comment>');

                continue;
            }

            $this->installConfig($package);
            $this->installRouting($package);
            $this->copyMigration($package);
            $this->copySeeders($package);
            $this->syncAssets($package);

            if (is_callable([$package, 'install'])) {
                $package->install($this);
            }
        }

        return true;
    }

    /**
     * installConfig
     *
     * @param AbstractPackage $package
     *
     * @return  void
     * @throws \ReflectionException
     */
    protected function installConfig(AbstractPackage $package)
    {
        $dir = $package->getDir() . '/Resources/config';

        // Config
        $targetFolder = $this->console->get('path.etc') . '/package';
        $file         = $dir . '/config.dist.php';
        $target       = $targetFolder . '/' . $package->name . '.php';

        if (is_file($file) && (new BooleanPrompter)->ask("File: <info>config.dist.php</info> exists,\n do you want to copy it to <comment>etc/package/" . $package->name . '.php</comment> [Y/n]: ',
                true)) {
            if (is_file($target) && (new BooleanPrompter)->ask('File exists, do you want to override it? [N/y]: ',
                    false)) {
                File::delete($target);
            } else {
                $this->out('  Config file: <comment>etc/package/' . $package->name . '.php</comment> exists, do not copy.');
            }

            if (!is_file($target) && File::copy($file, $target)) {
                $this->out('  Copy to <info>etc/package/' . $package->name . '.php</info> successfully.');
            }
        }

        $file   = $dir . '/secret.dist.yml';
        $target = $this->console->get('path.etc') . '/secret.yml';

        if (is_file($file) && with(new BooleanPrompter)->ask("File: <info>secret.dist.yml</info> exists,\n do you want to copy content to bottom of <comment>etc/secret.yml</comment> [Y/n]: ",
                true)) {
            $secret = ltrim(file_get_contents($target));
            $new    = file_get_contents($file);
            $secret = $secret . "\n# " . $package->name . "\n" . ltrim($new);

            File::write($target, $secret);

            $this->out('  Copy to <info>etc/secret.yml</info> successfully.');
        }
    }

    /**
     * installRouting
     *
     * @param   AbstractPackage $package
     *
     * @return  void
     */
    protected function installRouting(AbstractPackage $package)
    {
        if (!(new BooleanPrompter)->ask('Do your want to add routing profile to <comment>etc/routing.yml</comment>? [Y/n]: ',
            true)) {
            return;
        }

        $pattern = (new TextPrompter)->ask('The URL pattern prefix [<info>/' . $package->name . '</info>]: ',
            '/' . $package->name);

        $routing = <<<ROUTE

# Routing of package: %s
%s:
    pattern: %s
    package: %s

ROUTE;

        $routing = sprintf($routing, $package->name, $package->name, $pattern, $package->name);

        $target = $this->console->get('path.etc') . '/routing.yml';

        $content = file_get_contents($target);
        $content .= $routing;

        file_put_contents($target, $content);

        $this->out()->out('  Added routing profile to <comment>etc/routing.yml</comment>');
    }

    /**
     * syncAssets
     *
     * @param AbstractPackage $package
     *
     * @return  void
     */
    public function syncAssets(AbstractPackage $package)
    {
        $this->out();

        try {
            $this->console->executeByPath('asset sync ' . $package->name, ['hard' => $this->getOption('hard')]);
        } catch (\Exception $e) {
            $this->err($e->getMessage());
        }
    }

    /**
     * copyMigration
     *
     * @param AbstractPackage $package
     *
     * @return  void
     * @throws \ReflectionException
     */
    protected function copyMigration(AbstractPackage $package)
    {
        $dir = $package->getDir() . '/Migration';

        // Config
        $targetFolder = $this->console->get('path.resources') . '/migrations';

        $relativePath = str_replace($this->console->get('path.root') . DIRECTORY_SEPARATOR, '', $targetFolder);

        if (!is_dir($dir)) {
            return;
        }

        if (!(new BooleanPrompter)->ask('Do your want to copy migrations to <comment>' . $relativePath . '</comment>? [Y/n]: ',
            true)) {
            return;
        }

        $files = Folder::files($dir, true, Folder::PATH_RELATIVE);

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            $dest = new \SplFileInfo($targetFolder . '/' . $file);

            if (is_file($dest->getPathname())) {
                $this->out(sprintf('  [<comment>File exists</comment>] ' . $file));

                continue;
            }

            File::copy($dir . '/' . $file, $dest);

            $this->out(sprintf('  [<info>Copied</info>] ' . $file));
        }

        $this->out()->out('  Copy migrations completed.');
    }

    /**
     * copySeeders
     *
     * @param   AbstractPackage $package
     *
     * @return  void
     * @throws \ReflectionException
     */
    protected function copySeeders(AbstractPackage $package)
    {
        $dir = $package->getDir() . '/Seed';

        // Config
        $targetFolder = $this->console->get('path.resources') . '/seeders';

        $relativePath = str_replace($this->console->get('path.root') . DIRECTORY_SEPARATOR, '', $targetFolder);

        if (!is_dir($dir)) {
            return;
        }

        if (!(new BooleanPrompter)->ask('Do your want to copy seeders to <comment>' . $relativePath . '</comment>? [Y/n]: ',
            true)) {
            return;
        }

        $files = Folder::files($dir, true, Folder::PATH_RELATIVE);

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file == 'MainSeeder.php') {
                continue;
            }

            $dest = new \SplFileInfo($targetFolder . '/' . $file);

            if (is_file($dest->getPathname())) {
                $this->out(sprintf('  [<comment>File exists</comment>] ' . $file));

                continue;
            }

            File::copy($dir . '/' . $file, $dest);

            $this->out(sprintf('  [<info>Copied</info>] ' . $file));
        }

        $this->out()->out('  Copy seeders completed.');
    }
}
