<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Debugger\Provider;

use Windwalker\Core\Object\NullObject;
use Windwalker\Database\Driver\AbstractDatabaseDriver;
use Windwalker\Database\Middleware\DbProfilerMiddleware;
use Windwalker\Debugger\Listener\ProfilerListener;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Event\ListenerPriority;
use Windwalker\Profiler\NullProfiler;
use Windwalker\Profiler\Point\Point;
use Windwalker\Profiler\Profiler;
use Windwalker\Structure\Structure;

/**
 * The ProfilerProvider class.
 *
 * @since  2.1.1
 */
class ProfilerProvider implements ServiceProviderInterface
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
        $container = $container->getParent();

        // System profiler
        $closure = function (Container $container) {
            $config = $container->get('config');

            if ($config->get('system.debug')) {
                $profiler = new Profiler('windwalker');

                $profiler->setPoint(new Point('start', $config['execution.start'] ?: microtime(true),
                    $config['execution.memory'] ?: memory_get_usage(true), ['tag' => 'system.process']));

                return $profiler;
            } else {
                return new NullProfiler;
            }
        };

        $container->share('profiler', $closure);

        // System collector
        $closure = function (Container $container) {
            $config = $container->get('config');

            if ($config->get('system.debug')) {
                return new Structure;
            } else {
                return new NullObject;
            }
        };

        $container->share('debugger.collector', $closure);

        if ($container->get('config')->get('system.debug')) {
            $this->registerProfilerListener($container, $container->get('config'));
            $this->registerDatabaseProfiler($container, $container->get('config'));
            $this->registerEmailProfiler($container, $container->get('config'));
            $this->registerLogsProfiler($container, $container->get('config'));
        }
    }

    /**
     * registerProfilers
     *
     * @param Container $container
     * @param Structure $config
     *
     * @return  void
     */
    protected function registerDatabaseProfiler(Container $container, Structure $config)
    {
        if (!$container->exists('database')) {
            return;
        }

        static $queryData = [];

        $collector = $container->get('debugger.collector');

        $collector['database.query.times']        = 0;
        $collector['database.query.total.time']   = 0;
        $collector['database.query.total.memory'] = 0;
        $collector['database.queries']            = [];

        /** @var AbstractDatabaseDriver $db */
        $db = $container->get('database');

        // Set profiler to DatabaseDriver
        $db->addMiddleware(new DbProfilerMiddleware(
            function (AbstractDatabaseDriver $db, \stdClass $data) use ($container, $collector, &$queryData) {
                if (stripos(trim($data->sql), 'EXPLAIN') === 0) {
                    return;
                }

                $collector['database.query.times'] = $collector['database.query.times'] + 1;

                $queryData = [
                    'serial' => $collector['database.query.times'],
                    'time' => ['start' => microtime(true)],
                    'memory' => ['start' => memory_get_usage(false)],
                ];
            },
            function (AbstractDatabaseDriver $db, \stdClass $data) use ($container, $collector, &$queryData) {
                if (stripos(trim($data->sql), 'EXPLAIN') === 0) {
                    return;
                }

                $queryData['time']['end']        = microtime(true);
                $queryData['time']['duration']   = abs($queryData['time']['end'] - $queryData['time']['start']);
                $queryData['memory']['end']      = memory_get_usage(false);
                $queryData['memory']['duration'] = abs($queryData['memory']['end'] - $queryData['memory']['start']);
                $queryData['query']              = $db->getLastQuery();
                $queryData['rows']               = $db->getReader()->countAffected();

                $queryData = array_merge((array) $data, $queryData);

                $collector->push('database.queries', $queryData);

                $collector['database.query.total.time']   = $collector['database.query.total.time'] + $queryData['time']['duration'];
                $collector['database.query.total.memory'] = $collector['database.query.total.memory'] + $queryData['memory']['duration'];
            }
        ));
    }

    /**
     * registerProfilerListener
     *
     * @param Container $container
     * @param Structure $config
     *
     * @return  void
     */
    protected function registerProfilerListener(Container $container, Structure $config)
    {
        $dispatcher = $container->get('dispatcher');

        $dispatcher->setDebug(true);

        $dispatcher->addListener(new ProfilerListener, ListenerPriority::LOW);
    }

    /**
     * registerEmailProfiler
     *
     * @param Container $container
     * @param Structure $config
     *
     * @return  void
     */
    protected function registerEmailProfiler(Container $container, Structure $config)
    {
        // Not implemented yet
    }

    /**
     * registerLogsProfiler
     *
     * @param Container $container
     * @param Structure $config
     *
     * @return  void
     */
    protected function registerLogsProfiler(Container $container, Structure $config)
    {
        // Not implemented yet
    }
}
