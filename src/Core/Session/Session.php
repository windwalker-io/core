<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Session;

use Windwalker\Core\Facade\AbstractProxyFacade;
use Windwalker\Session\Bag\FlashBagInterface;
use Windwalker\Session\Bag\SessionBagInterface;
use Windwalker\Session\Bridge\SessionBridgeInterface;
use Windwalker\Session\Handler\HandlerInterface;
use Windwalker\Session\Session as WiSession;

/**
 * The Session class.
 * 
 * @see \Windwalker\Session\Session
 *
 * @method  static  boolean    start()
 * @method  static  boolean    destroy()
 * @method  static  boolean    restart()
 * @method  static  boolean    fork()
 * @method  static  WiSession  close()
 * @method  static  WiSession  regenerate($destroy = false)
 * @method  static  mixed      get($name, $default = null, $namespace = 'default')
 * @method  static  array      getAll($namespace = 'default')
 * @method  static  array      takeAll($namespace = 'default')
 * @method  static  WiSession  clean($namespace = 'default')
 * @method  static  WiSession  set($name, $value = null, $namespace = 'default')
 * @method  static  boolean    exists($name, $namespace = 'default')
 * @method  static  mixed      remove($name, $namespace = 'default')
 * @method  static  WiSession  addFlash($msg, $type = 'info')
 * @method  static  array      getFlashes()
 * @method  static  SessionBridgeInterface  getBridge()
 * @method  static  WiSession  setBridge($bridge)
 * @method  static  HandlerInterface  getHandler()
 * @method  static  WiSession  setHandler($handler)
 * @method  static  string     getName()
 * @method  static  string     getId()
 * @method  static  boolean    isActive()
 * @method  static  boolean    isNew()
 * @method  static  string     getState()
 * @method  static  WiSession  setState($state)
 * @method  static  array      getCookie()
 * @method  static  Session    setCookie($cookie)
 * @method  static  mixed      getOption($name, $default = null)
 * @method  static  WiSession  setOption($name, $value)
 * @method  static  array      getOptions()
 * @method  static  WiSession  setOptions($options)
 * @method  static  array      getBags()
 * @method  static  Session    setBags(array $bags)
 * @method  static  SessionBagInterface  getBag($name)
 * @method  static  WiSession  setBag($name, SessionBagInterface $bag)
 * @method  static  FlashBagInterface  getFlashBag()
 * @method  static  WiSession  setFlashBag(FlashBagInterface $bag)
 * @method  static  WiSession  setDebug($debug)
 *
 * @since  {DEPLOY_VERSION}
 */
class Session extends AbstractProxyFacade
{
	/**
	 * Property _key.
	 *
	 * @var  string
	 */
	protected static $_key = 'session';
}
