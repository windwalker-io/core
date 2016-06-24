<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Language;

use Windwalker\Core\Facade\AbstractProxyFacade;

/**
 * The Translator class.
 *
 * @see  CoreLanguage
 *
 * @method  static  string    translate($string)
 * @method  static  string    _($string)
 * @method  static  string    sprintf($string, ...$more)
 * @method  static  string    plural($string, ...$number)
 * @method  static  string    getOrphans()
 * @method  static  string    setTraceLevelOffset($level)
 * @method  static  string    getTraceLevelOffset()
 * @method  static  CoreLanguage  loadFile($file, $format = 'ini', $package = null)
 * @method  static  CoreLanguage  getInstance()
 *
 * @since  2.0
 */
abstract class Translator extends AbstractProxyFacade
{
	/**
	 * Property _key.
	 *
	 * @var  string
	 */
	protected static $_key = 'language';
}
