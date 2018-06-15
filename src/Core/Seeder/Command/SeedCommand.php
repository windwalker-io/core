<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Core\Seeder\Command;

use Windwalker\Console\Command\Command;
use Windwalker\Core\Mvc\MvcHelper;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Seeder\Command\Seed\ClearCommand;
use Windwalker\Core\Seeder\Command\Seed\ImportCommand;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Ioc;
use Windwalker\Loader\ClassLoader;
use Windwalker\String\StringNormalise;
use Windwalker\Utilities\Reflection\ReflectionHelper;

/**
 * Class Seed
 */
class SeedCommand extends Command
{
    /**
     * An enabled flag.
     *
     * @var bool
     */
    public static $isEnabled = true;

    /**
     * Console(Argument) name.
     *
     * @var  string
     */
    protected $name = 'seed';

    /**
     * The command description.
     *
     * @var  string
     */
    protected $description = 'The data seeder help you create fake data.';

    /**
     * The usage to tell user how to use this command.
     *
     * @var string
     */
    protected $usage = 'Database seeder <cmd><command></cmd> <option>[option]</option>';

    /**
     * Initialise command information.
     *
     * @return void
     */
    public function init()
    {
        $this->addCommand(ImportCommand::class);
        $this->addCommand(ClearCommand::class);

        $this->addGlobalOption('c')
            ->alias('class')
            ->defaultValue('MainSeeder')
            ->description('The class to import.');

        $this->addGlobalOption('d')
            ->alias('dir')
            ->description('The directory of this seeder.');

        $this->addGlobalOption('p')
            ->alias('package')
            ->description('Package name to import seeder.');

        parent::init();
    }

    /**
     * prepareExecute
     *
     * @return  void
     */
    protected function prepareExecute()
    {
        $packageName = $this->getOption('package');

        /** @var AbstractPackage $package */
        $package = $this->console->getPackage($packageName);

        $class = null;

        if ($package) {
            $class = MvcHelper::getPackageNamespace(get_class($package), 1) . '\\Seed\\DatabaseSeeder';
        }

        if (!class_exists($class)) {
            $class = $this->getOption('class');
        }

        $class = StringNormalise::toClassNamespace($class);

        if (!class_exists($class)) {
            $file = $package ? $package->getDir() . '/Seed/' . $class . '.php' : null;

            if (!$file || !is_file($file)) {
                $file = $this->getOption('d', Ioc::getConfig()->get('path.seeders')) . '/' . str_replace('\\',
                        DIRECTORY_SEPARATOR, $class) . '.php';

                if ($file[0] != '/' && substr($file, 1, 2) != ':\\') {
                    $file = $this->console->get('path.root') . '/' . $file;
                }
            }

            if (is_file($file)) {
                include_once $file;
            }
        }

        if (!class_exists($class)) {
            throw new \RuntimeException('Class: ' . $class . ' not exists.');
        }

        if (!is_subclass_of($class, 'Windwalker\Core\Seeder\AbstractSeeder')) {
            throw new \RuntimeException('Class: ' . $class . ' should be sub class of Windwalker\Core\Seeder\AbstractSeeder.');
        }

        // Auto include classes
        $path = dirname(ReflectionHelper::getPath($class));

        $files  = Filesystem::files($path);
        $loader = new ClassLoader();
        $loader->register();

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            $loader->addMap($file->getBasename('.php'), $file->getPathname());
        }

        $this->console->set('seed.class', $class);
    }

    /**
     * Execute this command.
     *
     * @return int|void
     */
    protected function doExecute()
    {
        return parent::doExecute();
    }
}
