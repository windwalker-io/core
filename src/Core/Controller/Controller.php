<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Controller;

use Windwalker\Controller\AbstractController;
use Windwalker\Core\Application\WebApplication;
use Windwalker\IO\Input;

/**
 * The Controller class.
 * 
 * @since  {DEPLOY_VERSION}
 */
abstract class Controller extends AbstractController
{
	/**
	 * Property input.
	 *
	 * @var  Input
	 */
	protected $input = null;

	/**
	 * Property app.
	 *
	 * @var  WebApplication
	 */
	protected $app = null;

	/**
	 * Property redirectUrl.
	 *
	 * @var  array
	 */
	protected $redirectUrl = array(
		'url' => null,
		'msg' => null,
		'type' => null,
	);

	/**
	 * Property mute.
	 *
	 * @var  boolean
	 */
	protected $mute = false;

	/**
	 * setRedirect
	 *
	 * @param string $url
	 * @param string $msg
	 * @param string $type
	 *
	 * @return  static
	 */
	public function setRedirect($url, $msg = null, $type = 'info')
	{
		$this->redirectUrl = array(
			'url' => $url,
			'msg' => $msg,
			'type' => $type,
		);

		return $this;
	}

	/**
	 * redirect
	 *
	 * @param string $url
	 * @param string $msg
	 * @param string $type
	 *
	 * @return  void
	 */
	public function redirect($url = null, $msg = null, $type = 'info')
	{
		if (!$this->app)
		{
			return;
		}

		if (!$url)
		{
			$url = $this->redirectUrl['url'];
			$msg = $this->redirectUrl['msg'];
			$type = $this->redirectUrl['type'];
		}

		if (!$url)
		{
			return;
		}

		$this->app->addFlash($msg, $type)->redirect($url);
	}

	/**
	 * addFlash
	 *
	 * @param string $msg
	 * @param string $type
	 *
	 * @return  static
	 */
	public function addFlash($msg, $type = 'info')
	{
		if (!$this->mute)
		{
			$this->app->addFlash($msg, $type);
		}

		return $this;
	}

	/**
	 * mute
	 *
	 * @param bool $bool
	 *
	 * @return  static
	 */
	public function mute($bool = true)
	{
		$this->mute = $bool;

		return $this;
	}

	/**
	 * isMute
	 *
	 * @return  bool
	 */
	public function isMute()
	{
		return $this->mute;
	}
}
