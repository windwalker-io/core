<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2019 LYRASOFT.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\SystemPackage\Command\Legacy;

use Symfony\Component\Yaml\Yaml;
use Windwalker\Core\Console\CoreCommand;
use Windwalker\Core\DateTime\Chronos;
use Windwalker\Structure\Format\PhpFormat;

/**
 * The ConvertRouting class.
 *
 * @since  3.5
 */
class ConvertConfigCommand extends CoreCommand
{
    /**
     * Console(Argument) name.
     *
     * @var  string
     */
    protected $name = 'convert-config';

    /**
     * The command description.
     *
     * @var  string
     */
    protected $description = 'Convert config from old yml to php.';

    /**
     * The usage to tell user how to use this command.
     *
     * @var string
     */
    protected $usage = '%s [<file pattern>] [options]';

    /**
     * Initialise command.
     *
     * @return void
     *
     * @since  3.5
     */
    protected function init()
    {
        $this->addOption('p')
            ->alias('package')
            ->description('Package name');
    }

    /**
     * Execute this command.
     *
     * @return int
     *
     * @since  2.0
     * @throws \Exception
     */
    protected function doExecute()
    {
        $path = getcwd() . '/' . $this->getArgument(0);

        if (!is_file($path)) {
            throw new \RuntimeException('Not file');
        }

        $content = Yaml::parse(file_get_contents($path));
        $year = Chronos::create()->year;

        $content = PhpFormat::structToString(
            $content,
            [
                'header' => "/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) $year __ORGANIZATION__.
 * @license    LGPL-2.0-or-later
 */"
            ]
        );

        $path = substr_replace($path, 'php', strrpos($path, '.') + 1);

        file_put_contents($path, $content);
        $this->out('Write to:' . $path);

        return true;
    }
}
