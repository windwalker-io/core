<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Widget;

use Windwalker\Core\Package\NullPackage;
use Windwalker\Utilities\Queue\PriorityQueue;
use Windwalker\Utilities\Reflection\ReflectionHelper;

/**
 * The WidgetComponent class.
 *
 * @since  3.0
 */
class WidgetComponent extends Widget
{
    /**
     * Property name.
     *
     * @var  string
     */
    protected $name;

    /**
     * Property layout.
     *
     * @var  string
     */
    protected $layout = null;

    /**
     * Class init.
     *
     * @param string $package
     */
    public function __construct($package = null)
    {
        $layout = $this->layout;

        if (!$layout) {
            $layout = $this->name ?: 'default';
        }

        parent::__construct($layout, null, $package);
    }

    /**
     * registerPaths
     *
     * @param bool $refresh
     *
     * @return static
     */
    public function registerPaths($refresh = false)
    {
        if (!$this->pathRegistered || $refresh) {
            $this->renderer->setPaths(new PriorityQueue());

            $package = $this->getPackage();

            if (!$package instanceof NullPackage) {
                $locale  = $package->app->get('language.locale', 'en-GB');
                $default = $package->app->get('language.default', 'en-GB');

                $selfPath = dirname(ReflectionHelper::getPath(get_called_class()));

                $this->renderer->addPath($selfPath . '/templates/' . $locale, PriorityQueue::BELOW_NORMAL);

                if ($default != $locale) {
                    $this->renderer->addPath($selfPath . '/templates/' . $default, PriorityQueue::BELOW_NORMAL);
                }

                $this->renderer->addPath($selfPath . '/templates', PriorityQueue::BELOW_NORMAL);

                if ($this->name) {
                    $globalPath = $this->getPackage()->app->get('path.templates');

                    $this->renderer->addPath($globalPath . '/' . $this->getName() . '/' . $locale,
                        PriorityQueue::BELOW_NORMAL);

                    if ($default != $locale) {
                        $this->renderer->addPath($globalPath . '/' . $this->getName() . '/' . $default,
                            PriorityQueue::BELOW_NORMAL);
                    }

                    $this->renderer->addPath($globalPath . '/' . $this->getName(), PriorityQueue::BELOW_NORMAL);
                }
            }
        }

        return $this;
    }

    /**
     * Method to get property Name
     *
     * @return  mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Method to set property name
     *
     * @param   mixed $name
     *
     * @return  static  Return self to support chaining.
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }
}
