<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Response;

use Psr\Http\Message\StreamInterface;
use Windwalker\Core\View\AbstractView;
use Windwalker\Http\Response\HtmlResponse;

/**
 * The HtmlViewResponse class.
 *
 * @since  3.0
 */
class HtmlViewResponse extends HtmlResponse
{
	/**
	 * Property view.
	 *
	 * @var  AbstractView
	 */
	protected $view;

	/**
	 * Method to get property View
	 *
	 * @return  AbstractView
	 */
	public function getView()
	{
		return $this->view;
	}

	/**
	 * Method to set property view
	 *
	 * @param   AbstractView $view
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function withView(AbstractView $view)
	{
		return new static($view, $this->getStatusCode(), $this->getHeaders());
	}

	/**
	 * Handle body to stream object.
	 *
	 * @param   string $body The body data.
	 *
	 * @return  StreamInterface  Converted to stream object.
	 */
	protected function handleBody($body)
	{
		if ($body instanceof AbstractView)
		{
			$this->view = $body;
		}

		return parent::handleBody((string) $body);
	}
}
