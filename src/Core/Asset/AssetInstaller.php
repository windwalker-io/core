<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Asset;

use Minify_CSS_Compressor;
use Windwalker\Console\Command\AbstractCommand;
use Windwalker\Filesystem\File;
use Windwalker\Filesystem\Folder;

/**
 * The AssetInstaller class.
 *
 * @since  3.0.1
 */
class AssetInstaller extends AbstractCommand
{
	/**
	 * Property hooks.
	 *
	 * @var  array
	 */
	protected $hooks = [];

	/**
	 * Property vendorPath.
	 *
	 * @var  string
	 */
	protected $vendorPath;

	/**
	 * Property assetPath.
	 *
	 * @var  string
	 */
	protected $assetPath;

	/**
	 * Property assets.
	 *
	 * @var  array
	 */
	protected $assets;

	/**
	 * AssetInstaller constructor.
	 *
	 * @param string $name
	 * @param string $vendorPath
	 * @param string $assetPath
	 * @param array  $assets
	 */
	public function __construct($name, $vendorPath, $assetPath, $assets = [])
	{
		$this->vendorPath = realpath($vendorPath);
		$this->assetPath = realpath($assetPath);
		$this->assets = $assets;

		if (!$this->vendorPath)
		{
			throw new \RuntimeException('Vendor path: ' . $vendorPath . ' not exists.');
		}

		if (!$this->assetPath)
		{
			throw new \RuntimeException('Asset path: ' . $assetPath . ' not exists.');
		}

		parent::__construct($name);
	}

	/**
	 * execute
	 *
	 * @return  void
	 */
	public function doExecute()
	{
		$vendors = (array) $this->assets;
		$installs = $this->io->getArguments();

		foreach ($installs as $vendor)
		{
			if (!isset($vendors[$vendor]))
			{
				continue;
			}

			$files = $vendors[$vendor];

			if (!is_dir($this->vendorPath . '/' . $vendor))
			{
				continue;
			}

			$this->runHook('before-' . $vendor, $vendor, $files);

			foreach ($files as $src => $dest)
			{
				$message = $this->copy($this->vendorPath . '/' . $vendor . '/' . $src, $this->assetPath . '/' . $dest);

				$this->out($message);
			}

			$this->runHook('after-' . $vendor, $vendor, $files);
		}

//		Folder::delete($this->getVendorPath());

		$this->out('Install complete');
	}

	/**
	 * addHook
	 *
	 * @param string   $name
	 * @param callable $callable
	 *
	 * @return  static
	 */
	public function addHook($name, callable $callable)
	{
		$this->hooks[$name] = $callable;

		return $this;
	}

	/**
	 * runHook
	 *
	 * @param   string $name
	 * @param   string $vendor
	 * @param   array  $assets
	 *
	 * @return mixed|null
	 */
	public function runHook($name, $vendor, array $assets = [])
	{
		if (!isset($this->hooks[$name]) || !is_callable($this->hooks[$name]))
		{
			return null;
		}

		return call_user_func($this->hooks[$name], $this, $vendor, $assets);
	}

	/**
	 * copy
	 *
	 * @param   string  $src
	 * @param   string  $dest
	 *
	 * @return  string
	 */
	protected function copy($src, $dest)
	{
		if (!is_file($src) && !is_dir($src))
		{
			return 'Source File or dir not found: ' . $src;
		}

		if (is_file($src))
		{
			File::copy($src, $dest, true);
		}
		elseif (is_dir($src))
		{
			Folder::copy($src, $dest, true);
		}

		return sprintf('Copy %s ===> %s', $src, $dest);
	}

	/**
	 * Method to get property VendorPath
	 *
	 * @return  string
	 */
	public function getVendorPath()
	{
		return $this->vendorPath;
	}

	/**
	 * Method to get property AssetPath
	 *
	 * @return  string
	 */
	public function getAssetPath()
	{
		return $this->assetPath;
	}

	/**
	 * Method to get property Assets
	 *
	 * @return  array
	 */
	public function getAssets()
	{
		return $this->assets;
	}

	/**
	 * minify
	 *
	 * @param string $file
	 * @param string $type
	 *
	 * @return  static
	 */
	public function minify($file, $type = null)
	{
		if ($type === null)
		{
			$type = File::getExtension($file);
		}

		$type = strtolower($type);

		if ($type == 'css')
		{
			$content = Minify_CSS_Compressor::process(file_get_contents($file));
		}
		elseif ($type == 'js')
		{
			$content = \JSMinPlus::minify(file_get_contents($file));
		}
		else
		{
			return $this;
		}

		$dest = dirname($file) . '/' . File::stripExtension(File::getFilename($file)) . '.min.' . $type;

		file_put_contents($dest, $content);

		$this->out('Minify file: ' . $dest);

		return $this;
	}
}
