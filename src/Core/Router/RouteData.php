<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2019 LYRASOFT.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\Router;

use Windwalker\Core\Application\WebApplication;

/**
 * The RouteData class.
 *
 * @method $this getAction(string|bool $controller)
 * @method $this postAction(string|bool $controller)
 * @method $this putAction(string|bool $controller)
 * @method $this patchAction(string|bool $controller)
 * @method $this deleteAction(string|bool $controller)
 * @method $this headAction(string|bool $controller)
 * @method $this optionsAction(string|bool $controller)
 *
 * @since  3.5
 */
class RouteData
{
    use RouteConfigureTrait;

    /**
     * Property name.
     *
     * @var string
     */
    protected $name;

    /**
     * Property groups.
     *
     * @var array
     */
    protected $groups = [];

    /**
     * RouteData constructor.
     *
     * @param string $name
     * @param array  $options
     */
    public function __construct(string $name = null, array $options = [])
    {
        $this->name    = $name;
        $this->options = $options;
    }

    /**
     * name
     *
     * @param string $name
     *
     * @return  static
     *
     * @since  3.5
     */
    public function name($name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * pattern
     *
     * @param string $value
     *
     * @return  static
     *
     * @since  3.5
     */
    public function pattern(string $value): self
    {
        $this->options['pattern'] = $value;

        return $this;
    }

    /**
     * pattern
     *
     * @param string|bool|callable $value
     *
     * @return  static
     *
     * @since  3.5
     */
    public function controller($value): self
    {
        $this->options['controller'] = $value;

        return $this;
    }

    /**
     * redirect
     *
     * @param  string|RouteString  $to
     * @param  int                 $code
     *
     * @return  static
     *
     * @since  __DEPLOY_VERSION__
     */
    public function redirect($to, int $code = 303): self
    {
        $this->controller(function (WebApplication $app) use ($to, $code) {
            $app->redirect((string) $to, $code);
        });

        return $this;
    }

    /**
     * group
     *
     * @param string $group
     *
     * @return  RouteData
     *
     * @since  3.5
     */
    public function group(string $group): self
    {
        $this->groups[] = $group;

        return $this;
    }

    /**
     * groups
     *
     * @param array $groups
     *
     * @return  RouteData
     *
     * @since  3.5
     */
    public function groups(array $groups): self
    {
        $this->groups = $groups;

        return $this;
    }

    /**
     * getGroups
     *
     * @return  array
     *
     * @since  3.5
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * Method to get property Name
     *
     * @return  string
     *
     * @since  3.5
     */
    public function getName()
    {
        return $this->name;
    }
}
