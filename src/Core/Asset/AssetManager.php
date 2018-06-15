<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Asset;

use Windwalker\Core\Config\Config;
use Windwalker\Dom\HtmlElement;
use Windwalker\Event\DispatcherAwareInterface;
use Windwalker\Event\DispatcherAwareTrait;
use Windwalker\Event\DispatcherInterface;
use Windwalker\Filesystem\File;
use Windwalker\Ioc;
use Windwalker\String\Str;
use Windwalker\Uri\UriData;
use Windwalker\Utilities\Arr;

/**
 * The AssetManager class.
 *
 * @property-read  UriData $uri
 *
 * @method  $this  addCSS($url, $version = null, $attribs = [])
 * @method  $this  addJS($url, $version = null, $attribs = [])
 * @method  $this  internalCSS($content)
 * @method  $this  internalJS($content)
 *
 * @since   3.0
 */
class AssetManager implements DispatcherAwareInterface
{
    use DispatcherAwareTrait;

    /**
     * Property styles.
     *
     * @var  array
     */
    protected $styles = [];

    /**
     * Property scripts.
     *
     * @var  array
     */
    protected $scripts = [];

    /**
     * Property aliases.
     *
     * @var  array
     */
    protected $aliases = [];

    /**
     * Property internalStyles.
     *
     * @var  array
     */
    protected $internalStyles = [];

    /**
     * Property internalScripts.
     *
     * @var  array
     */
    protected $internalScripts = [];

    /**
     * Property version.
     *
     * @var string
     */
    protected $version;

    /**
     * Property templates.
     *
     * @var  AssetTemplate
     */
    protected $template;

    /**
     * Property indents.
     *
     * @var  string
     */
    protected $indents = '    ';

    /**
     * Property path.
     *
     * @var  string
     */
    public $path;

    /**
     * Property root.
     *
     * @var  string
     */
    public $root;

    /**
     * Property config.
     *
     * @var  Config
     */
    protected $config;

    /**
     * Property uri.
     *
     * @var  UriData
     */
    protected $uri;

    /**
     * AssetManager constructor.
     *
     * @param Config              $config
     * @param UriData             $uri
     * @param DispatcherInterface $dispatcher
     */
    public function __construct(Config $config, UriData $uri, DispatcherInterface $dispatcher)
    {
        $this->path       = $config->get('asset.uri') ?: $uri->path . '/' . $config->get('asset.folder', 'asset');
        $this->root       = $config->get('asset.uri') ?: $uri->root . '/' . $config->get('asset.folder', 'asset');
        $this->config     = $config;
        $this->dispatcher = $dispatcher;
        $this->uri        = $uri;
    }

    /**
     * addStyle
     *
     * @param string $url
     * @param array  $options
     * @param array  $attribs
     *
     * @return  static
     */
    public function addStyle($url, $options = [], $attribs = [])
    {
        // B/C
        if (!is_array($options)) {
            $options = ['version' => $options];
        }

        $options = array_merge([
            'version' => null,
        ], $options);

        $file = [
            'url' => $this->handleUri($url),
            'attribs' => $attribs,
            'options' => $options,
        ];

        $this->styles[$url] = $file;

        return $this;
    }

    /**
     * addScript
     *
     * @param string $url
     * @param array  $options
     * @param array  $attribs
     *
     * @return  static
     */
    public function addScript($url, $options = [], $attribs = [])
    {
        // B/C
        if (!is_array($options)) {
            $options = ['version' => $options];
        }

        $options = array_merge([
            'version' => null,
        ], $options);

        $file = [
            'url' => $this->handleUri($url),
            'attribs' => $attribs,
            'options' => $options,
        ];

        $this->scripts[$url] = $file;

        return $this;
    }

    /**
     * import
     *
     * @param string $url
     * @param array  $options
     * @param array  $attribs
     *
     * @return  AssetManager
     *
     * @since       3.3
     *
     * @deprecated  HTML imports has been deprecated.
     */
    public function import($url, array $options = [], array $attribs = [])
    {
        if (Arr::get($options, 'as') === 'style') {
            $attribs['rel'] = 'import';

            return $this->addStyle($url, $options, $attribs);
        }

        $options['import'] = true;

        return $this->addScript($url, $options, $attribs);
    }

    /**
     * internalStyle
     *
     * @param string $content
     *
     * @return  static
     */
    public function internalStyle($content)
    {
        $this->internalStyles[] = (string) $content;

        return $this;
    }

    /**
     * internalStyle
     *
     * @param string $content
     *
     * @return  static
     */
    public function internalScript($content)
    {
        $this->internalScripts[] = (string) $content;

        return $this;
    }

    /**
     * Check asset uri exists in system and return actual path.
     *
     * @param string $uri    The file uri to check.
     * @param bool   $strict Check .min file or un-min file exists again if input file not exists.
     *
     * @return  bool|string
     *
     * @since  3.3
     */
    public function exists($uri, $strict = false)
    {
        if (static::isAbsoluteUrl($uri)) {
            return $uri;
        }

        $assetUri = $this->path;

        if (static::isAbsoluteUrl($assetUri)) {
            return rtrim($assetUri, '/') . '/' . ltrim($uri, '/');
        }

        $root = $this->addSysPath($assetUri);

        $this->normalizeUri($uri, $assetFile, $assetMinFile);

        if (is_file($root . '/' . $uri)) {
            return $this->addBase($uri, 'path');
        }

        if (!$strict) {
            if (is_file($root . '/' . $assetFile)) {
                return $this->addBase($assetFile, 'path');
            }

            if (is_file($root . '/' . $assetMinFile)) {
                return $this->addBase($assetMinFile, 'path');
            }
        }

        return false;
    }

    /**
     * renderStyles
     *
     * @param bool  $withInternal
     * @param array $internalAttrs
     *
     * @return string
     */
    public function renderStyles($withInternal = false, array $internalAttrs = [])
    {
        $html = [];

        Ioc::getApplication()->triggerEvent('onAssetRenderStyles', [
            'asset' => $this,
            'withInternal' => &$withInternal,
            'html' => &$html,
        ]);

        foreach ($this->styles as $url => $style) {
            $defaultAttribs = [
                'href' => $style['url'],
                'rel' => 'stylesheet',
            ];

            $attribs = array_merge($defaultAttribs, $style['attribs']);

            if ($style['options']['version'] !== false) {
                $attribs['href'] .= '?' . $this->getVersion();
            }

            if (isset($style['options']['sri'])) {
                $attribs['integrity']   = $style['options']['sri'];
                $attribs['crossorigin'] = 'anonymous';
            }

            if (isset($style['options']['conditional'])) {
                $html[] = '<!--[if ' . $style['options']['conditional'] . ']>';
            }

            $html[] = (string) new HtmlElement('link', null, $attribs);

            if (isset($style['options']['conditional'])) {
                $html[] = '<![endif]-->';
            }
        }

        if ($withInternal && $this->internalStyles) {
            $html[] = (string) new HtmlElement('style', "\n" . $this->renderInternalStyles() . "\n" . $this->indents, $internalAttrs);
        }

        return implode("\n" . $this->indents, $html);
    }

    /**
     * renderStyles
     *
     * @param bool  $withInternal
     * @param array $internalAttrs
     *
     * @return string
     */
    public function renderScripts($withInternal = false, array $internalAttrs = [])
    {
        $html = [];

        $this->triggerEvent('onAssetRenderScripts', [
                'asset' => $this,
                'withInternal' => &$withInternal,
                'html' => &$html,
            ]
        );

        foreach ($this->scripts as $url => $script) {
            $defaultAttribs = [
                'src' => $script['url'],
            ];

            $attribs = array_merge($defaultAttribs, $script['attribs']);

            if ($script['options']['version'] !== false) {
                $attribs['src'] .= '?' . $this->getVersion();
            }

            if (isset($script['options']['sri'])) {
                $attribs['integrity']   = $script['options']['sri'];
                $attribs['crossorigin'] = 'anonymous';
            }

            if (isset($script['options']['conditional'])) {
                $html[] = '<!--[if ' . $script['options']['conditional'] . ']>';
            }

            if (isset($script['options']['import'])) {
                $attribs['href'] = $attribs['src'];
                $attribs['rel']  = 'import';
                unset($attribs['src']);
                $html[] = (string) new HtmlElement('link', null, $attribs);
            } else {
                $html[] = (string) new HtmlElement('script', null, $attribs);
            }

            if (isset($script['options']['conditional'])) {
                $html[] = '<![endif]-->';
            }
        }

        if ($withInternal && $this->internalScripts) {
            $html[] = (string) new HtmlElement('script', "\n" . $this->renderInternalScripts() . "\n" . $this->indents, $internalAttrs);
        }

        return implode("\n" . $this->indents, $html);
    }

    /**
     * renderInternalStyles
     *
     * @return  string
     */
    public function renderInternalStyles()
    {
        return implode("\n\n", $this->internalStyles);
    }

    /**
     * renderInternalStyles
     *
     * @return  string
     */
    public function renderInternalScripts()
    {
        return implode(";\n", $this->internalScripts);
    }

    /**
     * getVersion
     *
     * @return  string
     */
    public function getVersion()
    {
        if ($this->version) {
            return $this->version;
        }

        if ($this->config->get('system.debug')) {
            return $this->version = md5(uniqid('Windwalker-Asset-Version', true));
        }

        $sumFile = $this->config->get('path.cache') . '/asset/MD5SUM';

        if (!is_file($sumFile)) {
            return $this->version = $this->detectVersion();
        }

        return $this->version = trim(file_get_contents($sumFile));
    }

    /**
     * detectVersion
     *
     * @return  string
     */
    protected function detectVersion()
    {
        static $version;

        if ($version) {
            return $version;
        }

        $assetUri = $this->path;

        if (static::isAbsoluteUrl($assetUri)) {
            return $version = md5($assetUri . $this->config->get('system.secret', 'Windwalker-Asset'));
        }

        $time  = '';
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->addSysPath($assetUri),
            \FilesystemIterator::FOLLOW_SYMLINKS));

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isLink() || $file->isDir()) {
                continue;
            }

            $time .= $file->getMTime();
        }

        return $version = md5($this->config->get('system.secret', 'Windwalker-Asset') . $time);
    }

    /**
     * removeBase
     *
     * @param   string $assetUri
     *
     * @return  string
     */
    public function addSysPath($assetUri)
    {
        if (static::isAbsoluteUrl($assetUri)) {
            return $assetUri;
        }

        $assetUri = trim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $assetUri), '/\\');
        $base     = rtrim($this->config->get('path.public'), '/\\');

        if (!$base) {
            return '/';
        }

        $match = '';

        // @see http://stackoverflow.com/a/6704596
        for ($i = strlen($base) - 1; $i >= 0; $i -= 1) {
            $chunk = substr($base, $i);
            $len   = strlen($chunk);

            if (substr($assetUri, 0, $len) == $chunk && $len > strlen($match)) {
                $match = $chunk;
            }
        }

        return $base . DIRECTORY_SEPARATOR . ltrim(substr($assetUri, strlen($match)), '/\\');
    }

    /**
     * isAbsoluteUrl
     *
     * @param   string $uri
     *
     * @return  boolean
     */
    public static function isAbsoluteUrl($uri)
    {
        return stripos($uri, 'http') === 0 || strpos($uri, '//') === 0;
    }

    /**
     * Method to set property version
     *
     * @param   string $version
     *
     * @return  static  Return self to support chaining.
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Method to get property Styles
     *
     * @return  array
     */
    public function getStyles()
    {
        return $this->styles;
    }

    /**
     * Method to set property styles
     *
     * @param   array $styles
     *
     * @return  static  Return self to support chaining.
     */
    public function setStyles($styles)
    {
        $this->styles = $styles;

        return $this;
    }

    /**
     * Method to get property Scripts
     *
     * @return  array
     */
    public function getScripts()
    {
        return $this->scripts;
    }

    /**
     * Method to set property scripts
     *
     * @param   array $scripts
     *
     * @return  static  Return self to support chaining.
     */
    public function setScripts($scripts)
    {
        $this->scripts = $scripts;

        return $this;
    }

    /**
     * Method to get property InternalStyles
     *
     * @return  array
     */
    public function getInternalStyles()
    {
        return $this->internalStyles;
    }

    /**
     * Method to set property internalStyles
     *
     * @param   array $internalStyles
     *
     * @return  static  Return self to support chaining.
     */
    public function setInternalStyles($internalStyles)
    {
        $this->internalStyles = $internalStyles;

        return $this;
    }

    /**
     * Method to get property InternalScripts
     *
     * @return  array
     */
    public function getInternalScripts()
    {
        return $this->internalScripts;
    }

    /**
     * Method to set property internalScripts
     *
     * @param   array $internalScripts
     *
     * @return  static  Return self to support chaining.
     */
    public function setInternalScripts($internalScripts)
    {
        $this->internalScripts = $internalScripts;

        return $this;
    }

    /**
     * alias
     *
     * @param string $target
     * @param string $alias
     *
     * @return  static
     */
    public function alias($target, $alias)
    {
        $this->normalizeUri($target, $name);

        $this->aliases[$name] = $alias;

        return $this;
    }

    /**
     * resolveAlias
     *
     * @param   string $uri
     *
     * @return  string
     */
    public function resolveAlias($uri)
    {
        $this->normalizeUri($uri, $name);

        while (isset($this->aliases[$name])) {
            $name = $this->aliases[$name];
        }

        return $name;
    }

    /**
     * Method to set property indents
     *
     * @param   string $indents
     *
     * @return  static  Return self to support chaining.
     */
    public function setIndents($indents)
    {
        $this->indents = $indents;

        return $this;
    }

    /**
     * Method to get property Indents
     *
     * @return  string
     */
    public function getIndents()
    {
        return $this->indents;
    }

    /**
     * handleUri
     *
     * @param   string $uri
     *
     * @return  string
     */
    public function handleUri($uri)
    {
        $uri = $this->resolveAlias($uri);

        // Check has .min
        // $uri = Uri::addBase($uri, 'path');

        if (static::isAbsoluteUrl($uri)) {
            return $uri;
        }

        $assetUri = $this->path;

        if (static::isAbsoluteUrl($assetUri)) {
            return rtrim($assetUri, '/') . '/' . ltrim($uri, '/');
        }

        $root = $this->addSysPath($assetUri);

        $this->normalizeUri($uri, $assetFile, $assetMinFile);

        // Use uncompressed file first
        if ($this->config->get('system.debug')) {
            if (is_file($root . '/' . $assetFile)) {
                return $this->addBase($assetFile, 'path');
            }

            if (is_file($root . '/' . $assetMinFile)) {
                return $this->addBase($assetMinFile, 'path');
            }
        } else {
            // Use min file first
            if (is_file($root . '/' . $assetMinFile)) {
                return $this->addBase($assetMinFile, 'path');
            }

            if (is_file($root . '/' . $assetFile)) {
                return $this->addBase($assetFile, 'path');
            }
        }

        // All file not found, fallback to default uri.
        return $this->addBase($uri, 'path');
    }

    /**
     * normalizeUri
     *
     * @param   string $uri
     * @param   string $assetFile
     * @param   string $assetMinFile
     *
     * @return  array
     */
    public function normalizeUri($uri, &$assetFile = null, &$assetMinFile = null)
    {
        $ext = File::getExtension($uri);

        if (Str::endsWith($uri, '.min.' . $ext)) {
            $assetFile    = substr($uri, 0, -strlen('.min.' . $ext)) . '.' . $ext;
            $assetMinFile = $uri;
        } else {
            $assetMinFile = substr($uri, 0, -strlen('.' . $ext)) . '.min.' . $ext;
            $assetFile    = $uri;
        }

        return [$assetFile, $assetMinFile];
    }

    /**
     * addBase
     *
     * @param string $uri
     * @param string $path
     *
     * @return  string
     */
    public function addBase($uri, $path = 'path')
    {
        if (!static::isAbsoluteUrl($uri)) {
            $uri = $this->$path . '/' . $uri;
        }

        return $uri;
    }

    /**
     * Method to get property Template
     *
     * @return  AssetTemplate
     */
    public function getTemplate()
    {
        if (!$this->template) {
            return $this->template = new AssetTemplate();
        }

        return $this->template;
    }

    /**
     * Method to set property template
     *
     * @param   AssetTemplate $template
     *
     * @return  static  Return self to support chaining.
     */
    public function setTemplate(AssetTemplate $template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * __call
     *
     * @param   string $name
     * @param   array  $args
     *
     * @return  mixed
     */
    public function __call($name, $args)
    {
        switch ($name) {
            case 'addCSS':
                return $this->addStyle(...$args);
                break;

            case 'addJS':
                return $this->addScript(...$args);
                break;

            case 'internalCSS':
                return $this->internalStyle(...$args);
                break;

            case 'internalJS':
                return $this->internalScript(...$args);
                break;
        }

        throw new \BadMethodCallException(sprintf('Call to undefined method %s() of %s', $name, get_class($this)));
    }

    /**
     * Internal method to get a JavaScript object notation string from an array
     *
     * @param mixed $data     The data to convert to JavaScript object notation
     * @param bool  $quoteKey Quote json key or not.
     *
     * @return string JavaScript object notation representation of the array
     */
    public static function getJSObject($data, $quoteKey = false)
    {
        if ($data === null) {
            return 'null';
        }

        $output = '';

        switch (gettype($data)) {
            case 'boolean':
                $output .= $data ? 'true' : 'false';
                break;

            case 'float':
            case 'double':
            case 'integer':
                $output .= $data + 0;
                break;

            case 'array':
                if (!Arr::isAssociative($data)) {
                    $child = [];

                    foreach ($data as $value) {
                        $child[] = static::getJSObject($value, $quoteKey);
                    }

                    $output .= '[' . implode(',', $child) . ']';
                    break;
                }

            case 'object':
                $array = is_object($data) ? get_object_vars($data) : $data;

                $row = [];

                foreach ($array as $key => $value) {
                    $encodedKey = json_encode((string) $key);

                    if (!$quoteKey && preg_match('/[^0-9A-Za-z_]+/m', $key) == 0) {
                        $encodedKey = substr(substr($encodedKey, 0, -1), 1);
                    }

                    $row[] = $encodedKey . ':' . static::getJSObject($value, $quoteKey);
                }

                $output .= '{' . implode(',', $row) . '}';
                break;

            default:  // anything else is treated as a string
                return strpos($data, '\\') === 0 ? substr($data, 1) : json_encode($data);
                break;
        }

        return $output;
    }

    /**
     * Method to get property Path
     *
     * @param   string $uri
     *
     * @return string
     */
    public function path($uri = null)
    {
        if ($uri !== null) {
            return $this->path . '/' . $uri;
        }

        return $this->path;
    }

    /**
     * Method to get property Root
     *
     * @param  string $uri
     *
     * @return string
     */
    public function root($uri = null)
    {
        if ($uri !== null) {
            return $this->root . '/' . $uri;
        }

        return $this->root;
    }

    /**
     * Method to get property AssetFolder
     *
     * @return  string
     */
    public function getAssetFolder()
    {
        return $this->config->get('asset.folder', 'asset');
    }

    /**
     * Method to set property uri
     *
     * @param   UriData $uri
     *
     * @return  static  Return self to support chaining.
     */
    public function setUriData(UriData $uri)
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * __get
     *
     * @param   string $name
     *
     * @return  mixed
     */
    public function __get($name)
    {
        $allow = [
            'uri',
        ];

        if (in_array($name, $allow)) {
            return $this->$name;
        }

        throw new \OutOfRangeException(sprintf('Property %s not exists.', $name));
    }
}
