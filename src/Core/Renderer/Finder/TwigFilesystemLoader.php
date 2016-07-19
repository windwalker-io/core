<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Renderer\Finder;

/**
 * The TwigFilesystemLoader class.
 *
 * @since  3.0
 */
class TwigFilesystemLoader extends \Windwalker\Renderer\Twig\TwigFilesystemLoader
{
	/**
	 * Property finder.
	 *
	 * @var  PackageFinder
	 */
	protected $finder;

	/**
	 * TwigFilesystemLoader constructor.
	 *
	 * @param PackageFinder $finder
	 * @param array|string  $paths
	 * @param string        $separator
	 */
	public function __construct($finder, $paths, $separator = '.')
	{
		parent::__construct($paths, $separator);

		$this->finder = $finder;
	}

	/**
	 * normalizeName
	 *
	 * @param   string $name
	 *
	 * @return  string
	 */
	protected function normalizeName($name)
	{
		$path = $this->finder->find($name);

		if ($path && in_array($path, $this->paths[static::MAIN_NAMESPACE]))
		{
			$this->prependPath($path);
		}

		return parent::normalizeName($name);
	}
}
