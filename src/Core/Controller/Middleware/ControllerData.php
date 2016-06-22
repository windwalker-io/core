<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Controller\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Router\CoreRoute;
use Windwalker\Data\Data;
use Windwalker\DI\Container;
use Windwalker\IO\Input;

/**
 * The ControllerData class.
 *
 * @property Input                  input
 * @property boolean                mute
 * @property boolean                hmvc
 * @property WebApplication         app
 * @property ServerRequestInterface request
 * @property ResponseInterface      response
 * @property CoreRoute              router
 * @property Container              container
 * @property AbstractPackage        package
 *
 * @since  {DEPLOY_VERSION}
 */
class ControllerData extends Data
{

}
