<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Language;

use Windwalker\Core\Config\Config;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\PackageResolver;
use Windwalker\Language\Language;
use Windwalker\Language\LanguageNormalize;
use Windwalker\Structure\Structure;

/**
 * The CoreLanguage class.
 *
 * @since  3.0
 */
class CoreLanguage extends Language
{
    /**
     * Property config.
     *
     * @var  Structure
     */
    protected $config;

    /**
     * Property packageResolver.
     *
     * @var  PackageResolver
     */
    protected $packageResolver;

    /**
     * CoreLanguage constructor.
     *
     * @param Config          $config
     * @param PackageResolver $packageResolver
     */
    public function __construct(Config $config, PackageResolver $packageResolver)
    {
        $this->config          = $config;
        $this->packageResolver = $packageResolver;

        parent::__construct(
            $config->get('language.locale', 'en-GB'),
            $config->get('language.default', 'en-GB')
        );

        $debug     = $config['system.debug'] ?: false;
        $langDebug = $config['language.debug'] ?: false;

        $this->setDebug(($debug && $langDebug));
    }

    /**
     * setLocale
     *
     * @param   string $locale
     *
     * @return  Language  Return self to support chaining.
     */
    public function setLocale($locale)
    {
        $this->config->set('language.locale', $locale);

        return parent::setLocale($locale);
    }

    /**
     * getLocale
     *
     * @return  string
     */
    public function getLocale()
    {
        return $this->config->get('language.locale')
            ?: $this->config->get('language.default', 'en-GB');
    }

    /**
     * load
     *
     * @param string $file
     * @param string $format
     * @param string $package
     *
     * @return static
     * @throws \ReflectionException
     */
    public function loadFile($file, $format = 'ini', $package = null)
    {
        $config = $this->config;

        $format = $format ?: $config->get('language.format', 'ini');

        $default = $config['language.default'] ?: 'en-GB';
        $locale  = $config['language.locale'] ?: 'en-GB';

        $ext = $format == 'yaml' ? 'yml' : $format;

        $default = LanguageNormalize::toLanguageTag($default);
        $locale  = LanguageNormalize::toLanguageTag($locale);

        // If package name exists, we load package language first, that global can override it.
        if (is_string($package)) {
            $package = $this->packageResolver->getPackage($package);
        }

        if ($package instanceof AbstractPackage) {
            $path = $package->getDir() . '/Resources/language/%s/%s.%s';

            // Get Package language
            if (!$config->get('language.debug') || $locale == $default) {
                $this->loadLanguageFile(sprintf($path, $default, $file, $ext), $format);
            }

            // If locale not equals default locale, load it to override default
            if ($locale != $default) {
                $this->loadLanguageFile(sprintf($path, $locale, $file, $format), $format);
            }
        }

        // Get Global language
        $path = $config->get('path.languages') . '/%s/%s.%s';

        if (!$config->get('language.debug') || $locale == $default) {
            $this->loadLanguageFile(sprintf($path, $default, $file, $format), $format);
        }

        // If locale not equals default locale, load it to override default
        if ($locale != $default) {
            $this->loadLanguageFile(sprintf($path, $locale, $file, $format), $format);
        }

        return $this;
    }

    /**
     * loadFile
     *
     * @param string $file
     * @param string $format
     *
     * @return  static
     */
    protected function loadLanguageFile($file, $format)
    {
        if (is_file($file)) {
            $this->load($file, $format);

            $loaded = $this->config['language.loaded'];

            $loaded[] = $file;

            $this->config->set('language.loaded', $loaded);
        }

        return $this;
    }

    /**
     * Alias of translate().
     *
     * @param string $string
     *
     * @return  string
     * @throws \ReflectionException
     */
    public function _($string)
    {
        $this->setTraceLevelOffset(1);

        $result = $this->translate($string);

        $this->setTraceLevelOffset(0);

        return $result;
    }
}
