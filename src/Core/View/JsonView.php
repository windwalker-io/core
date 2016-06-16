<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\View;

use Windwalker\Registry\Registry;

/**
 * The PhpJsonView class.
 *
 * @since  2.1.5.3
 */
class JsonView extends AbstractView
{
	/**
	 * Property data.
	 *
	 * @var  array|Registry
	 */
	protected $data = [];

	/**
	 * Property options.
	 *
	 * @var integer
	 */
	protected $options;

	/**
	 * Property depth.
	 *
	 * @var  integer
	 */
	protected $depth = 512;

	/**
	 * Method to instantiate the view.
	 *
	 * @param   array  $data  The data array.
	 */
	public function __construct(array $data = [])
	{
		parent::__construct($data);

		// Init registry object.
		$this->data = new Registry($data);
	}

	/**
	 * doRender
	 *
	 * @param  Registry $registry
	 *
	 * @return string
	 */
	protected function doRender($registry)
	{
		if ($registry instanceof Registry)
		{
			return $registry->toString('json', array('options' => $this->options, 'depth' => $this->depth));
		}

		if (version_compare(PHP_VERSION, '5.5', '<'))
		{
			return json_encode($this->data, $this->options);
		}
		else
		{
			return json_encode($this->data, $this->options, $this->depth);
		}
	}

	/**
	 * getData
	 *
	 * @return  Registry
	 */
	public function getData()
	{
		if (!$this->data)
		{
			$this->data = new Registry;
		}

		return $this->data;
	}

	/**
	 * setData
	 *
	 * @param   array|Registry  $data
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setData($data)
	{
		$this->data = $data instanceof Registry ? $data : new Registry($data);

		return $this;
	}

	/**
	 * Method to get property Options
	 *
	 * @return  int
	 */
	public function getOptions()
	{
		return $this->options;
	}

	/**
	 * Method to set property options
	 *
	 * @param   int $options
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setOptions($options)
	{
		$this->options = $options;

		return $this;
	}

	/**
	 * Method to get property Depth
	 *
	 * @return  int
	 */
	public function getDepth()
	{
		return $this->depth;
	}

	/**
	 * Method to set property depth
	 *
	 * @param   int $depth
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setDepth($depth)
	{
		$this->depth = $depth;

		return $this;
	}
}
