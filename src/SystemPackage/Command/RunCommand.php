<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\SystemPackage\Command;

use Windwalker\Core\Console\ConsoleHelper;
use Windwalker\Core\Console\CoreCommand;
use Windwalker\Utilities\Arr;

/**
 * The DeployCommand class.
 *
 * @since  3.0
 */
class RunCommand extends CoreCommand
{
    /**
     * Property name.
     *
     * @var  string
     */
    protected $name = 'run';

    /**
     * Property description.
     *
     * @var  string
     */
    protected $description = 'Run custom scripts.';

    /**
     * The usage to tell user how to use this command.
     *
     * @var string
     *
     * @since  2.0
     */
    protected $usage = '%s <cmd><command1></cmd> <cmd><command2></cmd>... <option>[option]</option>';

    /**
     * init
     *
     * @return  void
     */
    protected function init()
    {
        $this->addOption('l')
            ->alias('list')
            ->description('List available scripts.')
            ->defaultValue(false);

        $this->addOption('e')
            ->alias('ignore-error')
            ->description('Ignore error script and still run next.')
            ->defaultValue(false);
    }

    /**
     * doExecute
     *
     * @return  boolean
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     */
    protected function doExecute()
    {
        ini_set('max_execution_time', 0);

        $resolver = ConsoleHelper::getAllPackagesResolver();

        $scripts[] = (array) $this->console->get('console.scripts');

        foreach ((array) ConsoleHelper::loadPackages() as $name => $package) {
            if (!$package = $resolver->getPackage($name)) {
                continue;
            }

            $scripts[] = (array) $package->get('console.scripts');
        }

        $scripts = array_merge(...$scripts);

        if ($this->getOption('l')) {
            $this->listScripts($scripts);

            return true;
        }

        $profiles = $this->io->getArguments();

        if (!$profiles) {
            throw new \InvalidArgumentException('Please enter at least one profile name.');
        }

        foreach ($profiles as $profile) {
            $this->out()->out('Start Custom Script: <info>' . $profile . '</info>')
                ->out('---------------------------------------');

            if (isset($scripts[$profile])) {
                $this->executeScriptProfile($scripts[$profile]);
            }
        }

        $this->out()->out('Complete script running.');

        return true;
    }

    /**
     * executeScriptProfile
     *
     * @param array $scripts
     *
     * @return  void
     * @throws \RuntimeException
     */
    protected function executeScriptProfile($scripts)
    {
        foreach ($scripts as $script) {
            if (!is_array($script)) {
                $script = ['cmd' => $script, 'in' => null, 'ignore_error' => false];
            }

            $command = Arr::get($script, 'cmd');
            $input   = Arr::get($script, 'in');
            $ignore  = $this->getOption('e', Arr::get($script, 'ignore_error'));

            $code = $this->executeScript($command, $input);

            if (!$ignore && $code !== 0) {
                throw new \UnexpectedValueException('Script Stop with exit code: ' . $code, $code);
            }
        }
    }

    /**
     * executeScript
     *
     * @param string $script
     * @param string $input
     *
     * @return int
     */
    protected function executeScript($script, $input = null)
    {
        return $this->console->runProcess($script, $input);
    }

    /**
     * listScripts
     *
     * @param array $scripts
     *
     * @return  void
     */
    protected function listScripts($scripts)
    {
        if (!$scripts) {
            $this->out()->out('No custom scripts.');

            return;
        }

        $this->out()->out('<comment>Available Scripts</comment>')
            ->out('-----------------------------');

        foreach ($scripts as $name => $script) {
            $this->out(sprintf('<info>%s</info>', $name));

            foreach ($script as $cmd) {
                if (!is_array($cmd)) {
                    $cmd = ['cmd' => $cmd, 'in' => null];
                }

                $input = Arr::get($cmd, 'in');
                $cmd   = Arr::get($cmd, 'cmd');

                $this->out('    <comment>$</comment> ' . $cmd, false);

                if ($input !== null) {
                    $this->out(sprintf('  <option>(Input: %s)</option>', preg_replace('/\s+/', ' ', $input)), false);
                }

                $this->out();
            }

            $this->out();
        }
    }
}
