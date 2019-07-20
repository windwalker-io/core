<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Mailer;

use Windwalker\Core\Config\Config;
use Windwalker\Core\Mailer\Adapter\MailerAdapterInterface;
use Windwalker\Core\Mailer\Adapter\SwiftMailerAdapter;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * The MailerProvider class.
 *
 * @since  3.0
 */
class MailerProvider implements ServiceProviderInterface
{
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container $container The DI container.
     *
     * @return  void
     */
    public function register(Container $container)
    {
        $container->share(MailerAdapterInterface::class, static function (Container $container) {
            $config = $container->get(Config::class);

            return $container->createSharedObject($config->get('mail.adapter', SwiftMailerAdapter::class));
        });

        $container->prepareSharedObject(MailerManager::class);
    }
}
