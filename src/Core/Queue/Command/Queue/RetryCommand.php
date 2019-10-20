<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 LYRASOFT.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\Queue\Command\Queue;

use Windwalker\Console\Exception\WrongArgumentException;
use Windwalker\Core\Console\CoreCommand;
use Windwalker\Utilities\Arr;

/**
 * The WorkerCommand class.
 *
 * @since  3.2
 */
class RetryCommand extends CoreCommand
{
    /**
     * Property name.
     *
     * @var  string
     */
    protected $name = 'retry';

    /**
     * Property description.
     *
     * @var  string
     */
    protected $description = 'Retry failed jobs.';

    /**
     * Property usage.
     *
     * @var  string
     */
    protected $usage = '%s <cmd><ids...></cmd> <option>[option]</option>';

    /**
     * init
     *
     * @return  void
     */
    protected function init()
    {
        $this->addOption('d')
            ->alias('delay')
            ->defaultValue(0)
            ->description('Delay time for failed job to wait next run.');

        $this->addOption('a')
            ->alias('all')
            ->defaultValue(false)
            ->description('Retry all failed jobs.');
    }

    /**
     * doExecute
     *
     * @return  bool
     */
    protected function doExecute()
    {
        $factory = $this->console->container->get('queue.manager');
        $failer  = $this->console->container->get('queue.failer');

        $all = $this->getOption('all');

        if ($all) {
            $ids = array_column($failer->all(), 'id');
        } else {
            $ids = $this->io->getArguments();

            if (!count($ids)) {
                throw new WrongArgumentException('No id provided');
            }
        }

        $delay = $this->getOption('delay');

        foreach ($ids as $id) {
            $failed = $failer->get($id);

            $connection = Arr::get($failed, 'connection', null);

            $manager = $factory->getManager($connection);

            $manager->pushRaw(json_decode($failed['body'], true), $delay, $failed['queue']);

            if (!$all) {
                $this->out(sprintf(
                    'Resend failed-job: <info>%s</info> to connection: <option>%s</option> queue: <option>%s</option>',
                    $id,
                    $connection,
                    $failed['queue']
                ));

                $failer->remove($id);
            }
        }

        if ($all) {
            $this->out('Flush all failed-jobs');

            $failer->clear();
        }

        return true;
    }
}
