<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\View;

use Windwalker\Registry\Format\XmlFormat;

/**
 * The PhpXmlView class.
 *
 * @since  {DEPLOY_VERSION}
 */
class PhpXmlView extends AbstractView
{
	/**
	 * Property data.
	 *
	 * @var  array|Registry
	 */
	protected $data = array();

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
	public function __construct(array $data = array())
	{
		// Init registry object.
		$this->data = new \SimpleXMLElement(XmlFormat::structToString($data, array('name' => $this->root, 'nodeName' => $this->nodeName)));

		$this->initialise();
	}

	/**
	 * prepareData
	 *
	 * @param \SimpleXMLElement $registry
	 *
	 * @return  void
	 */
	protected function prepareData($registry)
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
		$this->data = $data;

		return $this;
	}
}
