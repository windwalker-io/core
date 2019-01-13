<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2019 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\SystemPackage\Command\Legacy;

use Symfony\Component\Yaml\Yaml;
use Windwalker\Core\Console\ConsoleHelper;
use Windwalker\Core\Console\CoreCommand;
use Windwalker\Core\DateTime\Chronos;
use Windwalker\String\StringNormalise;
use Windwalker\Structure\Format\PhpFormat;
use Windwalker\Utilities\Arr;

/**
 * The ConvertRouting class.
 *
 * @since  3.5
 */
class ConvertRoutingCommand extends CoreCommand
{
    /**
     * Console(Argument) name.
     *
     * @var  string
     */
    protected $name = 'convert-routing';

    /**
     * The command description.
     *
     * @var  string
     */
    protected $description = 'Convert routing from old yml to php.';

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
     * @since  2.0
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
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     * @since  2.0
     */
    protected function doExecute()
    {
        $paths = $this->getArgument(0);

        $paths = Arr::toArray($paths);

        if ($paths === []) {
            $p = $this->getOption('p');

            $dir = ConsoleHelper::getAllPackagesResolver()->getPackage($p)->getDir();

            $paths = [
                $dir . '/routing.yml',
                $dir . '/Resources/routing/**/*.yml'
            ];
        }

        $files = [];

        foreach ($paths as $path) {
            $files[] = \Windwalker\glob($path);
        }

        $files = array_merge(...$files);

        foreach ($files as $file) {
            $this->convert($file);
        }

        return true;
    }

    /**
     * convert
     *
     * @param string $file
     *
     * @return  void
     *
     * @since  3.5
     * @throws \Exception
     */
    protected function convert(string $file): void
    {
        $routes = Yaml::parse(file_get_contents($file));

        $year = Chronos::create()->year;

        $tmpl = <<<PHP
<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) $year __ORGANIZATION__.
 * @license    __LICENSE__
 */

use Windwalker\Core\Router\RouteCreator;

/** @var \$router RouteCreator */

PHP;

        foreach ($routes as $name => $route) {
            $code = '// ' . str(StringNormalise::toSpaceSeparated($name))->upperCaseWords() . "\n";
            $code .= "\$router->any('$name', '{$route['pattern']}')";

            if (isset($route['controller'])) {
                $code .= "\n" . $this->indent(1) . "->controller('{$route['controller']}')";
            }

            if (isset($route['variables'])) {
                $vars = $this->indent(1, PhpFormat::structToString($route['variables'], ['as_array' => true]));

                $code .= "\n" . $this->indent(1) . "->variables({$vars})";
            }

            if (isset($route['requirements'])) {
                $vars = $this->indent(1, PhpFormat::structToString($route['requirements'], ['as_array' => true]));

                $code .= "\n" . $this->indent(1) . "->requirements({$vars})";
            }

            if (isset($route['methods'])) {
                $vars = $this->indent(1, PhpFormat::structToString($route['methods'], ['as_array' => true]));

                $code .= "\n" . $this->indent(1) . "->methods({$vars})";
            }

            if (isset($route['action'])) {
                foreach ($route['action'] as $methods => $action) {
                    $methods = array_map('trim', explode('|', $methods));

                    foreach ($methods as $method) {
                        $method = strtolower($method);

                        if ($method === '*') {
                            $code .= "\n" . $this->indent(1) . "->allActions('{$action}')";
                        } else {
                            $code .= "\n" . $this->indent(1) . "->{$method}Action('{$action}')";
                        }
                    }
                }
            }

            if (isset($route['scheme'])) {
                $code .= "\n" . $this->indent(1) . "->scheme('{$route['scheme']}')";
            }

            if (isset($route['port'])) {
                $code .= "\n" . $this->indent(1) . "->port('{$route['port']}')";
            }

            if (isset($route['sslPort'])) {
                $code .= "\n" . $this->indent(1) . "->sslPort('{$route['sslPort']}')";
            }

            if (isset($route['extra'])) {
                $vars = $this->indent(1, PhpFormat::structToString($route['extra'], ['as_array' => true]));

                $code .= "\n" . $this->indent(1) . "->extraValues({$vars})";
            }

            $code .= ';';

            $tmpl .= "\n" . $code . "\n";
        }

        $dest = preg_replace('/\.[^.]+$/', '.', $file) . 'php';

        file_put_contents($dest, $tmpl);

        $this->out(sprintf('[Convert] <info>%s</info>', $dest));
    }

    /**
     * indent
     *
     * @param int         $level
     * @param string|null $content
     *
     * @return  string
     *
     * @since  3.5
     */
    protected function indent(int $level = 0, ?string $content = null): string
    {
        if (!$content) {
            return str_repeat('    ', $level);
        }

        return str_replace("\n", "\n" . str_repeat('    ', $level), $content);
    }
}
