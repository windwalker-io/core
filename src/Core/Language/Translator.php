<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Language;

use Windwalker\Core\Facade\AbstractProxyFacade;
use Windwalker\Core\Ioc;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\PackageHelper;
use Windwalker\Filesystem\File;
use Windwalker\Filesystem\Folder;
use Windwalker\Language\LanguageNormalize;
use Windwalker\Structure\Structure;
use Windwalker\Structure\StructureHelper;

/**
 * The Translator class.
 *
 * @see    CoreLanguage
 *
 * @method  static string    translate($string)
 * @method  static string    _($string)
 * @method  static string    sprintf($string, ...$more)
 * @method  static string    plural($string, ...$number)
 * @method  static bool      exists(string $key, bool $normalize = true)
 * @method  static bool      has(string $key, bool $normalize = true)
 * @method  static CoreLanguage  load(string $file, string $format = 'ini', string $loader = 'file')
 * @method  static CoreLanguage  addString(string $key, string $string)
 * @method  static CoreLanguage  addStrings(array $strings)
 * @method  static array     getOrphans()
 * @method  static string    setTraceLevelOffset($level)
 * @method  static string    getTraceLevelOffset()
 * @method  static string    getLocale()
 * @method  static string    getDefaultLocale()
 * @method  static CoreLanguage  loadFile($file, $format = 'ini', $package = null)
 * @method  static CoreLanguage  getInstance($forceNew = false)
 * @method  static array         getStrings()
 * @method  static CoreLanguage  setStrings(array $strings)
 * @method  static callable      getNormalizeHandler()
 *
 * @since  2.0
 */
abstract class Translator extends AbstractProxyFacade
{
    /**
     * Property _key.
     *
     * @var  string
     * phpcs:disable
    */
    protected static $_key = 'language';
    // phpcs:enable

    /**
     * loadAll
     *
     * @param string|AbstractPackage $package
     * @param string                 $format
     *
     * @return  void
     * @throws \ReflectionException
     */
    public static function loadAll($package, $format = 'ini')
    {
        if (is_string($package)) {
            $package = PackageHelper::getPackage($package);
        }

        if (!$package) {
            return;
        }

        $path = $package->getDir() . '/Resources/language';

        static::loadAllFromPath($path, $format, $package);
    }

    /**
     * loadAllFromPath
     *
     * @param   string $path
     * @param   string $format
     * @param   string $package
     */
    public static function loadAllFromPath($path, $format, $package = null)
    {
        $config = Ioc::getConfig();

        $locale  = $config['language.locale'] ?: 'en-GB';
        $default = $config['language.default'] ?: 'en-GB';
        $locale  = LanguageNormalize::toLanguageTag($locale);
        $default = LanguageNormalize::toLanguageTag($default);

        $localePath = $path . '/' . $locale;

        $files = [];

        if (is_dir($localePath)) {
            $files = array_merge($files, (array) Folder::files($localePath, false, Folder::PATH_BASENAME));
        }

        $defaultPath = $path . '/' . $default;

        if (is_dir($defaultPath)) {
            $files = array_merge($files, (array) Folder::files($defaultPath, false, Folder::PATH_BASENAME));
        }

        foreach ($files as $file) {
            $ext = File::getExtension($file);

            if (strcasecmp($ext, $format) !== 0) {
                continue;
            }

            static::loadFile(File::stripExtension($file), strtolower($format), $package);
        }
    }

    /**
     * getOrphans
     *
     * @param boolean $flatten
     * @param int     $stripPrefix
     *
     * @return array
     */
    public static function getAutoHandledOrphans($flatten = true, $stripPrefix = 1)
    {
        $orphans = static::getOrphans();

        foreach ($orphans as $key => $value) {
            $value = explode('.', $key);

            $value = array_map('ucfirst', $value);

            foreach (range(1, $stripPrefix) as $i) {
                array_shift($value);
            }

            $value = implode(' ', $value);

            $orphans[$key] = $value;
        }

        if (!$flatten) {
            $reg = new Structure();

            foreach ($orphans as $key => $value) {
                $reg->set($key, $value);
            }

            $orphans = $reg->toArray();
        }

        return $orphans;
    }

    /**
     * getFormattedOrphans
     *
     * @param string $format
     *
     * @return  string
     */
    public static function getFormattedOrphans($format = 'ini')
    {
        $formatter = StructureHelper::getFormatClass($format);

        $returns = [];
        $options = [];

        switch (strtolower($format)) {
            case 'ini':
                $orphans = static::getAutoHandledOrphans();

                foreach ($orphans as $key => $value) {
                    $key2 = explode('.', $key);

                    if (isset($key2[1])) {
                        $returns[$key2[1]][$key] = $value;

                        continue;
                    }

                    $returns[$key] = $value;
                }

                break;

            case 'yaml':
            case 'yml':
                $options['inline'] = 99;
            // No break
            default:
                $orphans = static::getAutoHandledOrphans(false);
                $returns = $orphans;
        }

        return $formatter::structToString($returns, $options);
    }

    /**
     * dumpOrphans
     *
     * @param string $format
     *
     * @return  void
     *
     * @throws \Windwalker\Filesystem\Exception\FilesystemException
     */
    public static function dumpOrphans($format = 'ini')
    {
        $format = strtolower($format);
        $ext    = ($format === 'yaml') ? 'yml' : $format;

        $file = WINDWALKER_LOGS . '/language-orphans.' . $ext;

        if (!is_file($file)) {
            Folder::create(dirname($file));
            file_put_contents($file, '');
        }

        $orphans = new Structure();
        $orphans->loadFile($file, $format, ['processSections' => true]);

        $orphans->loadString(static::getFormattedOrphans($format), $format, ['processSections' => true]);

        file_put_contents($file, $orphans->toString($format, ['inline' => 99]));
    }

    /**
     * Alias of exists()
     *
     * @param   string $format
     *
     * @return  string
     *
     * @throws \DomainException
     */
    public static function getFormatter($format)
    {
        $class = sprintf('Windwalker\Registry\Format\%sFormat', ucfirst($format));

        if (class_exists($class)) {
            return $class;
        }

        throw new \DomainException(sprintf('Class: %s not exists', $class));
    }
}
