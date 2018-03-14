<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Debugger\Controller\Asset;

use Windwalker\Core\Controller\AbstractController;
use Windwalker\Http\Response\TextResponse;

/**
 * The GetController class.
 *
 * @since  3.0
 */
class GetController extends AbstractController
{
    /**
     * doExecute
     *
     * @return  mixed
     */
    protected function doExecute()
    {
        $type = $this->input->get('type', 'css');

        $media       = WINDWALKER_DEBUGGER_ROOT . '/Resources/asset';
        $content     = '';
        $contentType = 'text/html';

        switch ($type) {
            case 'css':
                $content     .= file_get_contents($media . '/css/bootstrap.min.css');
                $content     .= file_get_contents($media . '/css/fontawesome5.min.css');
                $content     .= file_get_contents($media . '/css/microtip.css');
                $content     .= file_get_contents($media . '/css/debugger.css');
                $contentType = 'text/css';
                break;

            case 'fonts':
                $content     .= file_get_contents($media . '/webfonts/fa-solid-900.ttf');
                $contentType = null;
                break;
        }

        $this->response = (new TextResponse)->withContentType($contentType);
        $this->app->server->cachable(true);

        return $content;
    }

    /**
     * returnFail
     *
     * @param int $code
     *
     * @return  string
     */
    protected function returnFail($code = 404)
    {
        $this->app->response->setHeader('STATUS', $code);

        return false;
    }
}
