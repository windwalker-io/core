<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\View;

use Windwalker\Structure\Format\XmlFormat;

/**
 * The PhpXmlView class.
 *
 * @since  3.0
 */
class SimpleXmlView extends AbstractView
{
	/**
	 * Property data.
	 *
	 * @var  array|\SimpleXMLElement
	 */
	protected $data = [];

	/**
	 * Property root.
	 *
	 * @var  string
	 */
	protected $root = 'windwalker';

	/**
	 * Property nodeName.
	 *
	 * @var  string
	 */
	protected $nodeName = 'node';

	/**
	 * Method to instantiate the view.
	 *
	 * @param   array  $data  The data array.
	 */
	public function __construct($data = null)
	{
		parent::__construct();

		// Init registry object.
		if ($data instanceof \SimpleXMLElement)
		{
			$this->data = $data;
		}
		else
		{
			$this->data = new \SimpleXMLElement(XmlFormat::structToString($data, array('name' => $this->root, 'nodeName' => $this->nodeName)));
		}
	}

	/**
	 * prepareData
	 *
	 * @param \SimpleXMLElement $xml
	 *
	 * @return  void
	 */
	protected function prepareData($xml)
	{
	}

	/**
	 * doRender
	 *
	 * @param  \SimpleXMLElement $registry
	 *
	 * @return string
	 */
	protected function doRender($registry)
	{
		return $this->data->asXML();
	}

	/**
	 * getData
	 *
	 * @return  \SimpleXMLElement
	 */
	public function getData()
	{
		if (!$this->data)
		{
			$this->data = new \SimpleXMLElement("<{$this->root} />");
		}

		return $this->data;
	}

	/**
	 * setData
	 *
	 * @param   \SimpleXMLElement $data
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setData($data)
	{
		if (!$data instanceof \SimpleXMLElement)
		{
			throw new \InvalidArgumentException(__METHOD__ . ' argument should be instance of ' . \SimpleXMLElement::class);
		}

		$this->data = $data;

		return $this;
	}
}
