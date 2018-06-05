<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Package;

use Windwalker\Core\Mvc\ControllerResolver;
use Windwalker\Core\Mvc\MvcResolver;
use Windwalker\Core\Mvc\RepositoryResolver;
use Windwalker\Core\Mvc\ViewResolver;
use Windwalker\Core\Package\Resolver\DataMapperResolver;
use Windwalker\Core\Package\Resolver\RecordResolver;
use Windwalker\Core\Router\PackageRouter;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Form\FieldHelper;
use Windwalker\Form\ValidatorHelper;

/**
 * The PackageProvider class.
 *
 * @since  3.0
 */
class PackageProvider implements ServiceProviderInterface
{
    use PackageAwareTrait;

    /**
     * PackageProvider constructor.
     *
     * @param AbstractPackage $package
     */
    public function __construct(AbstractPackage $package)
    {
        $this->package = $package;
    }

    /**
     * boot
     *
     * @return  void
     * @throws \ReflectionException
     */
    public function boot()
    {
        $ns = (new \ReflectionClass($this->package))->getNamespaceName();

        RecordResolver::addNamespace($ns . '\Record');
        DataMapperResolver::addNamespace($ns . '\DataMapper');
        FieldHelper::addNamespace($ns . '\Field');
        ValidatorHelper::addNamespace($ns . 'Validator');
        // FieldDefinitionResolver::addNamespace($ns . '\Form');
    }

    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container $container The DI container.
     *
     * @return  void
     */
    public function register(Container $container)
    {
        $container->share(get_class($this->package), $this->package)
            ->bindShared(AbstractPackage::class, get_class($this->package));

        $container->share('controller.resolver', function (Container $container) {
            return new ControllerResolver($this->package, $container);
        });

        $container->share('repository.resolver', function (Container $container) {
            return new RepositoryResolver($this->package, $container);
        })->alias('model.resolver', 'repository.resolver');

        $container->share('view.resolver', function (Container $container) {
            return new ViewResolver($this->package, $container);
        });

        $container->share('mvc.resolver', function (Container $container) {
            return new MvcResolver(
                $container->get('controller.resolver'),
                $container->get('repository.resolver'),
                $container->get('view.resolver')
            );
        });

        if ($this->package->app->isWeb()) {
            // Router
            $container->prepareSharedObject(PackageRouter::class)->alias('router', PackageRouter::class);
        }
    }
}
