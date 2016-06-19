<?php
/**
 * Part of Phoenix project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Asset;

use Windwalker\Core\Utilities\Classes\OptionAccessTrait;
use Windwalker\Dom\HtmlElement;
use Windwalker\Event\DispatcherAwareInterface;
use Windwalker\Event\DispatcherAwareTrait;
use Windwalker\Filesystem\File;
use Windwalker\Ioc;
use Windwalker\String\StringHelper;
use Windwalker\Utilities\ArrayHelper;

/**
 * The AssetManager class.
 *
 * @method  static  addCSS($url, $version = null, $attribs = array())
 * @method  static  addJS($url, $version = null, $attribs = array())
 * @method  static  internalCSS($content)
 * @method  static  internalJS($content)
 *
 * @since   3.0
 */
class AssetManager implements DispatcherAwareInterface
{
	use DispatcherAwareTrait;
	use OptionAccessTrait;

	/**
	 * Property styles.
	 *
	 * @var  array
	 */
	protected $styles = array();

	/**
	 * Property scripts.
	 *
	 * @var  array
	 */
	protected $scripts = array();

	/**
	 * Property internalStyles.
	 *
	 * @var  array
	 */
	protected $internalStyles = array();

	/**
	 * Property internalScripts.
	 *
	 * @var  array
	 */
	protected $internalScripts = array();

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
	 * AssetManager constructor.
	 *
	 * @param array $options
	 */
	public function __construct($options = [])
	{
		$this->options = $options;
		
		$this->path = $this->getOption('uri_path') ? : Ioc::getUriData()->path . '/asset';
		$this->root = $this->getOption('uri_root') ? : Ioc::getUriData()->root . '/asset';
	}

	/**
	 * addStyle
	 *
	 * @param string $url
	 * @param string $version
	 * @param array  $attribs
	 *
	 * @return  static
	 */
	public function addStyle($url, $version = null, $attribs = array())
	{
		if (!$version && $version !== false)
		{
			$version = $this->getVersion();
		}

		$file = array(
			'url' => $this->handleUri($url),
			'attribs' => $attribs,
			'version' => $version
		);

		$this->styles[$url] = $file;

		return $this;
	}

	/**
	 * addScript
	 *
	 * @param string $url
	 * @param string $version
	 * @param array  $attribs
	 *
	 * @return  static
	 */
	public function addScript($url, $version = null, $attribs = array())
	{
		if (!$version && $version !== false)
		{
			$version = $this->getVersion();
		}

		$file = array(
			'url' => $this->handleUri($url),
			'attribs' => $attribs,
			'version' => $version
		);

		$this->scripts[$url] = $file;

		return $this;
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
		$this->internalStyles[] = $content;

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
		$this->internalScripts[] = $content;

		return $this;
	}

	/**
	 * renderStyles
	 *
	 * @param bool $withInternal
	 *
	 * @return string
	 */
	public function renderStyles($withInternal = false)
	{
		$html = array();

		Ioc::getApplication()->triggerEvent('onAssetRenderStyles', array(
			'asset' => $this,
			'withInternal' => &$withInternal,
			'html' => &$html
		));

		foreach ($this->styles as $url => $style)
		{
			$defaultAttribs = array(
				'rel' => 'stylesheet',
				'href' => $style['url']
			);

			$attribs = array_merge($defaultAttribs, $style['attribs']);

			if ($style['version'] !== false)
			{
				$attribs['href'] .= '?' . $style['version'];
			}

			$html[] = (string) new HtmlElement('link', null, $attribs);
		}

		if ($withInternal && $this->internalStyles)
		{
			$html[] = (string) new HtmlElement('style', "\n" . $this->renderInternalStyles() . "\n" . $this->indents);
		}

		return implode("\n" . $this->indents, $html);
	}

	/**
	 * renderStyles
	 *
	 * @param bool $withInternal
	 *
	 * @return string
	 */
	public function renderScripts($withInternal = false)
	{
		$html = array();

		$this->triggerEvent('onPhoenixRenderScripts', array(
			'asset' => $this,
			'withInternal' => &$withInternal,
			'html' => &$html
		));

		foreach ($this->scripts as $url => $script)
		{
			$defaultAttribs = array(
				'src' => $script['url']
			);

			$attribs = array_merge($defaultAttribs, $script['attribs']);

			if ($script['version'] !== false)
			{
				$attribs['src'] .= '?' . $script['version'];
			}

			$html[] = (string) new HtmlElement('script', null, $attribs);
		}

		if ($withInternal && $this->internalScripts)
		{
			$html[] = (string) new HtmlElement('script', "\n" . $this->renderInternalScripts() . "\n" . $this->indents);
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
		if ($this->version)
		{
			return $this->version;
		}

		$sumFile = WINDWALKER_CACHE . '/phoenix/asset/MD5SUM';

		if (!is_file($sumFile))
		{
			if (WINDWALKER_DEBUG)
			{
				return $this->version = md5(uniqid());
			}
			else
			{
				return $this->version = $this->detectVersion();
			}
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

		if ($version)
		{
			return $version;
		}

		$assetUri = $this->path;

		if (strpos($assetUri, 'http') === 0 | strpos($assetUri, '//') === 0)
		{
			return $version = md5($assetUri . $this->getOption('secret', 'Windwalker-Asset'));
		}
		else
		{
			$path = $this->addSysPath($assetUri);
		}

		$time = '';
		$files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::FOLLOW_SYMLINKS));

		/** @var \SplFileInfo $file */
		foreach ($files as $file)
		{
			$time .= $file->getMTime();
		}

		return $version = md5($this->getOption('secret', 'Windwalker-Asset') . $time);
	}

	/**
	 * removeBase
	 *
	 * @param   string  $assetUri
	 *
	 * @return  string
	 */
	protected function addSysPath($assetUri)
	{
		$assetUri = trim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $assetUri), '/\\');
		$base = trim($this->getOption('public_sys_path'), '/\\');

		if (!$base)
		{
			return '/';
		}

		$match = '';

		// @see http://stackoverflow.com/a/6704596
		for ($i = strlen($base) - 1; $i >= 0; $i -= 1) 
		{
			$chunk = substr($base, $i);
			$len = strlen($chunk);
			
			if (substr($assetUri, 0, $len) == $chunk && $len > strlen($match)) 
			{
				$match = $chunk;
			}
		}

		return $base . substr($assetUri, strlen($match));
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
	 * @param   string  $uri
	 *
	 * @return  string
	 */
	protected function handleUri($uri)
	{
		// Check has .min
		// $uri = Uri::addBase($uri, 'path');

		if (strpos($uri, 'http') === 0 || strpos($uri, '//') === 0)
		{
			return $uri;
		}

		$ext = File::getExtension($uri);

		$assetUri = trim($this->path, '/');

		if (strpos($assetUri, 'http') === 0 || strpos($assetUri, '//') === 0)
		{
			return $assetUri . '/' . ltrim($uri, '/');
		}

		$root = $this->addSysPath($assetUri);

		if (StringHelper::endsWith($uri, '.min.' . $ext))
		{
			$assetFile = substr($uri, 0, -strlen('.min.' . $ext)) . '.' . $ext;
			$assetMinFile = $uri;
		}
		else
		{
			$assetMinFile = substr($uri, 0, -strlen('.' . $ext)) . '.min.' . $ext;
			$assetFile = $uri;
		}

		// Use uncompressed file first
		if (WINDWALKER_DEBUG)
		{
			if (is_file($root . '/' . $assetFile))
			{
				return $this->addBase($assetFile, 'path');
			}

			if (is_file($root . '/' . $assetMinFile))
			{
				return $this->addBase($assetMinFile, 'path');
			}
		}

		// Use min file first
		else
		{
			if (is_file($root . '/' . $assetMinFile))
			{
				return $this->addBase($assetMinFile, 'path');
			}

			if (is_file($root . '/' . $assetFile))
			{
				return $this->addBase($assetFile, 'path');
			}
		}

		// All file not found, fallback to default uri.
		return $this->addBase($uri, 'path');
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
		if (strpos($uri, 'http') !== 0 && strpos($uri, '//') !== 0)
		{
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
		if (!$this->template)
		{
			return $this->template = new AssetTemplate;
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
	 * @param   string  $name
	 * @param   array   $args
	 *
	 * @return  mixed
	 */
	public function __call($name, $args)
	{
		switch ($name)
		{
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
	 * @param mixed $data
	 * @param bool  $quoteKey
	 *
	 * @return string JavaScript object notation representation of the array
	 */
	public static function getJSObject($data, $quoteKey = true)
	{
		if ($data === null)
		{
			return 'null';
		};

		$output = '';

		switch (gettype($data))
		{
			case 'boolean':
				$output .= $data ? 'true' : 'false';
				break;

			case 'float':
			case 'double':
			case 'integer':
				$output .= $data + 0;
				break;

			case 'array':
				if (!ArrayHelper::isAssociative($data))
				{
					$child = array();

					foreach ($data as $value)
					{
						$child[] = static::getJSObject($value, $quoteKey);
					}

					$output .= '[' . implode(',', $child) . ']';
					break;
				}

			case 'object':
				$array = is_object($data) ? get_object_vars($data) : $data;

				$row = array();

				foreach ($array as $key => $value)
				{
					$key = json_encode($key);

					if (!$quoteKey)
					{
						$key = substr(substr($key, 0, -1), 1);
					}

					$row[] = $key . ':' . static::getJSObject($value, $quoteKey);
				}

				$output .= '{' . implode(',', $row) . '}';
				break;

			default:  // anything else is treated as a string
				return strpos($data, '\\') === 0 ? substr($data, 1) : json_encode($data);
				break;
		}

		return $output;
	}
}
