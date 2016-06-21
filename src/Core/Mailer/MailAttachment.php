<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Mailer;

use Windwalker\Core\Utilities\Classes\OptionAccessTrait;

/**
 * The MailAttachment class.
 *
 * @since  {DEPLOY_VERSION}
 */
class MailAttachment
{
	use OptionAccessTrait;

	/**
	 * Property filename.
	 *
	 * @var  string
	 */
	protected $filename;

	/**
	 * Property contentType.
	 *
	 * @var  string
	 */
	protected $contentType;

	/**
	 * Property fileData.
	 *
	 * @var  string
	 */
	protected $body;

	/**
	 * MailAttachment constructor.
	 *
	 * @param string $filePath
	 * @param string $contentType
	 */
	public function __construct($filePath = null, $contentType = null)
	{
		if (is_file($filePath))
		{
			$this->loadFile($filePath);
		}

		$this->contentType = $contentType;
	}

	/**
	 * loadFile
	 *
	 * @param   string  $file
	 *
	 * @return  static
	 */
	public function loadFile($file)
	{
		if (!is_file($file))
		{
			throw new \RuntimeException(sprintf('File: %s not found.', $file));
		}

		$this->body = function () use ($file)
		{
		    return file_get_contents($file);
		};

		return $this;
	}

	/**
	 * Method to get property ContentType
	 *
	 * @return  string
	 */
	public function getContentType()
	{
		return $this->contentType;
	}

	/**
	 * Method to set property contentType
	 *
	 * @param   string $contentType
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setContentType($contentType)
	{
		$this->contentType = $contentType;

		return $this;
	}

	/**
	 * Method to get property Filename
	 *
	 * @return  string
	 */
	public function getFilename()
	{
		return $this->filename;
	}

	/**
	 * Method to set property filename
	 *
	 * @param   string $filename
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setFilename($filename)
	{
		$this->filename = $filename;

		return $this;
	}

	/**
	 * Method to get property Body
	 *
	 * @return  string
	 */
	public function getBody()
	{
		if ($this->body instanceof \Closure)
		{
			$this->body = call_user_func($this->body);
		}

		return $this->body;
	}

	/**
	 * Method to set property body
	 *
	 * @param   string $body
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setBody($body)
	{
		$this->body = $body;

		return $this;
	}
}
