<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Core\Renderer\Twig;

use Windwalker\DI\Container;

/**
 * Class WindwalkerExtension
 *
 * @since 1.0
 */
class WindwalkerExtension extends \Twig_Extension
{
    /**
     * Property container.
     *
     * @var  Container
     */
    protected $container;

    /**
     * WindwalkerExtension constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'windwalker';
    }

    /**
     * getGlobals
     *
     * @return  array
     */
    public function getGlobals()
    {
        return [];
    }

    /**
     * getFunctions
     *
     * @return  array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('show', 'show'),
        ];
    }

    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return array An array of filters
     */
    public function getFilters()
    {
        $language = $this->container->get('language');

        return [
            new \Twig_SimpleFilter('trans', [$language, 'translate']),
            new \Twig_SimpleFilter('lang', [$language, 'translate']),
            new \Twig_SimpleFilter('translate', [$language, 'translate']),
            new \Twig_SimpleFilter('_', [$language, 'translate']),
            new \Twig_SimpleFilter('sprintf', function (...$args) use ($language) {
                return $language->sprintf(...$args);
            }),
            new \Twig_SimpleFilter('plural', function (...$args) use ($language) {
                return $language->plural(...$args);
            }),
        ];
    }

    /**
     * Method to get property Container
     *
     * @return  Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Method to set property container
     *
     * @param   Container $container
     *
     * @return  static  Return self to support chaining.
     */
    public function setContainer($container)
    {
        $this->container = $container;

        return $this;
    }
}
