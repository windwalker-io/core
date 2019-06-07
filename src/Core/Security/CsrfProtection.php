<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Security;

use Windwalker\Core\Facade\AbstractProxyFacade;
use Windwalker\Dom\HtmlElement;

/**
 * The CsrfProtection class.
 *
 * @see    CsrfGuard
 *
 * @method  static boolean      validate($justDie = false, $message = 'Invalid Token')  {
 * @throws  Exception\InvalidTokenException
 * }
 * @method  static boolean      checkToken($userId = null, $method = null)
 * @method  static HtmlElement  input($userId = null, $attribs = [])
 * @method  static string       createToken($length = 12)
 * @method  static string       getToken($forceNew = false)
 * @method  static string       getFormToken($userId = null, $forceNew = false)
 * @method  static string       getMessage()
 * @method  static CsrfGuard    setMessage(string $message)
 *
 * @since  2.0.9
 */
abstract class CsrfProtection extends AbstractProxyFacade
{
    public const TOKEN_KEY = 'form.token';

    /**
     * Property _key.
     *
     * @var  string
     * phpcs:disable
    */
    protected static $_key = 'security.csrf';
    // phpcs:enable
}
