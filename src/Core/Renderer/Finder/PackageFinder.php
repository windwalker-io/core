<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Renderer\Finder;

use Windwalker\Core\Package\PackageResolver;
use Windwalker\String\StringHelper;

/**
 * The RendererFinder class.
 *
 * @since  3.0
 */
class PackageFinder implements PackageFinderInterface
{
    /**
     * Property separator.
     *
     * @var  string
     */
    protected $separator = '@';

    /**
     * Property packageResolver.
     *
     * @var  PackageResolver
     */
    protected $packageResolver;

    /**
     * PackageFinder constructor.
     *
     * @param PackageResolver $packageResolver
     */
    public function __construct(PackageResolver $packageResolver)
    {
        $this->packageResolver = $packageResolver;
    }

    /**
     * find
     *
     * @param  string $file
     *
     * @return string
     * @throws \ReflectionException
     */
    public function find(&$file)
    {
        list($package, $file) = StringHelper::explode($this->separator, $file, 2, 'array_unshift');

        if (!$package) {
            return false;
        }

        $package = $this->packageResolver->getPackage($package);

        if (!$package) {
            return false;
        }

        return $package->getDir() . '/Templates';
    }

    /**
     * Method to get property Separator
     *
     * @return  string
     */
    public function getSeparator()
    {
        return $this->separator;
    }

    /**
     * Method to set property separator
     *
     * @param   string $separator
     *
     * @return  static  Return self to support chaining.
     */
    public function setSeparator($separator)
    {
        $this->separator = $separator;

        return $this;
    }

    /**
     * Method to get property PackageResolver
     *
     * @return  PackageResolver
     */
    public function getPackageResolver()
    {
        return $this->packageResolver;
    }

    /**
     * Method to set property packageResolver
     *
     * @param   PackageResolver $packageResolver
     *
     * @return  static  Return self to support chaining.
     */
    public function setPackageResolver($packageResolver)
    {
        $this->packageResolver = $packageResolver;

        return $this;
    }
}
