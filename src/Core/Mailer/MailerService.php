<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2019 .
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Mailer;

use Windwalker\Core\Config\Config;
use Windwalker\DI\Container;

/**
 * The MailerService class.
 *
 * @since  __DEPLOY_VERSION__
 */
class MailerService
{
    /**
     * Property config.
     *
     * @var  Config
     */
    protected $config;

    /**
     * Property container.
     *
     * @var  Container
     */
    protected $container;

    /**
     * MailerService constructor.
     *
     * @param Config    $config
     * @param Container $container
     */
    public function __construct(Config $config, Container $container)
    {
        $this->config = $config;
        $this->container = $container;
    }

    public function getMailer(?string $connection = null)
    {

    }

    public function createMailer(?string $connection = null, array $options = [])
    {

    }

    public function getAdapter(array $options = [])
    {

    }

    public function createAdapter(array $options = [])
    {

    }
}
