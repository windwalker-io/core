<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Mvc;

use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Repository\Repository;
use Windwalker\DI\Container;
use Windwalker\Utilities\Reflection\ReflectionHelper;

/**
 * The RepositoryResolver class.
 *
 * @since  __DEPLOY_VERSION__
 */
class RepositoryResolver extends AbstractClassResolver
{
    /**
     * Property baseClass.
     *
     * @var  string
     */
    protected $baseClass = Repository::class;

    /**
     * Property modelResolver.
     *
     * @var  ModelResolver
     */
    protected $modelResolver;

    /**
     * ControllerResolver constructor.
     *
     * @param AbstractPackage $package
     * @param Container       $container
     * @param array           $namespaces
     */
    public function __construct(AbstractPackage $package, Container $container = null, array $namespaces = [])
    {
        parent::__construct($package, $container, $namespaces);

        $this->modelResolver = new ModelResolver($package, $container, $namespaces);
    }

    /**
     * Get container key prefix.
     *
     * @return  string
     */
    public static function getPrefix()
    {
        return 'repository';
    }

    /**
     * If didn't found any exists class, fallback to default class which in current package..
     *
     * @return string Found class name.
     */
    protected function getDefaultNamespace()
    {
        return ReflectionHelper::getNamespaceName($this->package) . '\Repository';
    }

    /**
     * resolve
     *
     * @param string $name
     *
     * @return  string
     */
    public function resolve($name)
    {
        try {
            return parent::resolve($name);
        } catch (\DomainException $e) {
            if (substr($name, -10) === 'Repository') {
                $name = substr($name, 0, -10);
                $name .= 'Model';
            }

            try {
                return $this->modelResolver->resolve($name);
            } catch (\DomainException $ex) {
                throw $e;
            }
        }
    }

    /**
     * Method to get property ModelResolver
     *
     * @return  ModelResolver
     */
    public function getModelResolver()
    {
        return $this->modelResolver;
    }

    /**
     * Method to set property modelResolver
     *
     * @param   ModelResolver $modelResolver
     *
     * @return  static  Return self to support chaining.
     */
    public function setModelResolver($modelResolver)
    {
        $this->modelResolver = $modelResolver;

        return $this;
    }
}
