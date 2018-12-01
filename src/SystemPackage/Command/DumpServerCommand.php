<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2018 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\SystemPackage\Command;

use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Server\DumpServer;
use Windwalker\Core\Console\CoreCommand;

/**
 * The DumpServerCommand class.
 *
 * @since  3.4.6
 */
class DumpServerCommand extends CoreCommand
{
    /**
     * Property name.
     *
     * @var  string
     */
    protected $name = 'dump-server';

    /**
     * Property description.
     *
     * @var  string
     */
    protected $description = 'Start dump server.';

    /**
     * Execute this command.
     *
     * @return int
     *
     * @since  2.0
     */
    protected function doExecute()
    {
        if (!class_exists(DumpServer::class)) {
            throw new \DomainException('Please install symfony/var-dumper ^4.1 first.');
        }

        $host = $this->getArgument(0, $this->console->get('system.var_dump_server_host', 'tcp://127.0.0.1:9912'));

        $server = new DumpServer($host);

        $server->start();

        $this->out(sprintf('Server listening on <info>%s</info>', $server->getHost()));
        $this->out('Quit server with <cmd>Control + C</cmd>');

        $server->listen(function (Data $data, array $context, $clientId) {
            $this->console->addMessage('Data dumped...');

            $dumper = new CliDumper();
            $dumper->dump($data);

            $this->out()->out();
        });

        return true;
    }
}
