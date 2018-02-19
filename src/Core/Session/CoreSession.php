<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Session;

use Windwalker\Core\Facade\AbstractProxyFacade;
use Windwalker\Session\Bag\FlashBagInterface;
use Windwalker\Session\Bag\SessionBagInterface;
use Windwalker\Session\Bridge\SessionBridgeInterface;
use Windwalker\Session\Handler\HandlerInterface;
use Windwalker\Session\Session;

/**
 * The Session class.
 *
 * @see    \Windwalker\Session\Session
 *
 * @method  static boolean    start()
 * @method  static boolean    destroy()
 * @method  static boolean    restart()
 * @method  static boolean    fork()
 * @method  static Session  close()
 * @method  static Session  regenerate($destroy = false)
 * @method  static mixed      get($name, $default = null, $namespace = 'default')
 * @method  static array      getAll($namespace = 'default')
 * @method  static array      takeAll($namespace = 'default')
 * @method  static Session  clean($namespace = 'default')
 * @method  static Session  set($name, $value = null, $namespace = 'default')
 * @method  static boolean    exists($name, $namespace = 'default')
 * @method  static mixed      remove($name, $namespace = 'default')
 * @method  static Session  addFlash($msg, $type = 'info')
 * @method  static array      getFlashes()
 * @method  static SessionBridgeInterface  getBridge()
 * @method  static Session  setBridge($bridge)
 * @method  static HandlerInterface  getHandler()
 * @method  static Session  setHandler($handler)
 * @method  static string     getName()
 * @method  static string     getId()
 * @method  static boolean    isActive()
 * @method  static boolean    isNew()
 * @method  static string     getState()
 * @method  static Session  setState($state)
 * @method  static array      getCookie()
 * @method  static Session    setCookie($cookie)
 * @method  static mixed      getOption($name, $default = null)
 * @method  static Session  setOption($name, $value)
 * @method  static array      getOptions()
 * @method  static Session  setOptions($options)
 * @method  static array      getBags()
 * @method  static Session    setBags(array $bags)
 * @method  static SessionBagInterface  getBag($name)
 * @method  static Session  setBag($name, SessionBagInterface $bag)
 * @method  static FlashBagInterface  getFlashBag()
 * @method  static Session  setFlashBag(FlashBagInterface $bag)
 * @method  static Session  setDebug($debug)
 *
 * @since  3.0
 */
class CoreSession extends AbstractProxyFacade
{
    /**
     * Property _key.
     *
     * @var  string
     */
    protected static $_key = 'session';
}
