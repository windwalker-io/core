<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Controller;

/**
 * The MultiActionController class.
 * 
 * @since  {DEPLOY_VERSION}
 */
class MultiActionController extends Controller
{
	/**
	 * Property task.
	 *
	 * @var string
	 */
	protected $action = null;

	/**
	 * Property arguments.
	 *
	 * @var  array
	 */
	protected $arguments = array();

	/**
	 * Execute the controller.
	 *
	 * @throws  \LogicException
	 * @return  mixed Return executed result.
	 */
	public function execute()
	{
		$action = $this->action ? : 'indexAction';

		if (!is_callable(array($this, $action)))
		{
			throw new \LogicException(get_called_class() . '::' . $action . '() not exists.');
		}

		return call_user_func_array(array($this, $action), $this->arguments);
	}

	/**
	 * Method to get property Arguments
	 *
	 * @return  array
	 */
	public function getArguments()
	{
		return $this->arguments;
	}

	/**
	 * Method to set property arguments
	 *
	 * @param   array $arguments
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setArguments($arguments)
	{
		$this->arguments = $arguments;

		return $this;
	}

	/**
	 * Method to get property Action
	 *
	 * @return  string
	 */
	public function getActionName()
	{
		return $this->action;
	}

	/**
	 * Method to set property action
	 *
	 * @param   string $action
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setActionName($action)
	{
		$this->action = $action;

		return $this;
	}
}
