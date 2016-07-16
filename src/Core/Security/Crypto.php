<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Security;

use Windwalker\Core\Facade\AbstractProxyFacade;
use Windwalker\Crypt\Crypt;
use Windwalker\Crypt\CryptInterface;

/**
 * The Crypto class.
 *
 * @see  Crypt
 * @see  CryptInterface
 *
 * @method  static  string   encrypt($string, $key = null, $iv = null)
 * @method  static  string   decrypt($string, $key = null, $iv = null)
 * @method  static  boolean  verify($string, $hash, $key = null, $iv = null)
 * @method  static  Crypt    setKey($key)
 * @method  static  string   getKey($key)
 * @method  static  Crypt    getIV($key)
 * @method  static  string   setIV($iv)
 *
 * @since  {DEPLOY_VERSION}
 */
class Crypto extends AbstractProxyFacade
{
	/**
	 * Property _key.
	 *
	 * @var  string
	 */
	protected static $_key = 'crypt';
}
