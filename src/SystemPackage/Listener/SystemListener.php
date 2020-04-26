<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\SystemPackage\Listener;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Widget\WidgetHelper;
use Windwalker\Debugger\Helper\DebuggerHelper;
use Windwalker\Event\Event;
use Windwalker\Http\Response\HtmlResponse;
use Windwalker\Structure\Structure;
use Windwalker\Utilities\Classes\OptionAccessTrait;

/**
 * The SystemListener class.
 *
 * NOTE: This listener has been registered after onAfterInitialise event. So the first event is onAfterRouting.
 *
 * @since  2.1.1
 */
class SystemListener
{
    use OptionAccessTrait;

    /**
     * SystemListener constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * onAfterInitialise
     *
     * @param Event $event
     *
     * @return  void
     */
    public function onAfterInitialise(Event $event): void
    {
        /** @var WebApplication $app */
        $app = $event['app'];

        // Remove index.php
        if ($app->uri->script === 'index.php') {
            $app->uri->script = null;
        }
    }
}
