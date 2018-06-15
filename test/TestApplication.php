<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Test;

use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Ioc;
use Windwalker\Database\Test\TestDsnResolver;

/**
 * The TestApplication class.
 *
 * @since  2.1.1
 */
class TestApplication extends WebApplication
{
    /**
     * Property name.
     *
     * @var  string
     */
    protected $name = 'test';

    /**
     * Property configPath.
     *
     * @var  string
     */
    protected $rootPath = WINDWALKER_ROOT;

    /**
     * initialise
     *
     * @return  void
     */
    protected function init()
    {
        $this->boot();

        restore_error_handler();
        restore_exception_handler();

        // Resolve DB info
        $dsn = TestDsnResolver::getDsn($this->get('database.driver'));

        $this->config['database.host'] = $dsn['host'];
        // $this->config['database.name'] = $dsn['dbname'];
        $this->config['database.user']     = $dsn['user'];
        $this->config['database.password'] = $dsn['pass'];
        $this->config['database.prefix']   = $dsn['prefix'];
        $this->config['database.dsn']      = $dsn;

        // Start session
        Ioc::getSession()->start();
    }
}
