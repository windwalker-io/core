<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Attributes\Ref;
use Windwalker\Core\Manager\SessionManager;
use Windwalker\Core\Security\CsrfService;
use Windwalker\DI\Container;
use Windwalker\DI\Exception\DefinitionException;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Http\Event\ResponseEvent;
use Windwalker\Session\Cookie\ArrayCookies;
use Windwalker\Session\Cookie\Cookies;
use Windwalker\Session\Session;
use Windwalker\Session\SessionInterface;
use Windwalker\Session\SessionPackage;

/**
 * The SessionProvider class.
 *
 * @deprecated
 */
class SessionProvider extends SessionPackage
{
    //
}
