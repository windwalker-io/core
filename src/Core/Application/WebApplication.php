<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Application;

use Windwalker\Application\AbstractWebApplication;
use Windwalker\Application\Web\Response;
use Windwalker\Application\Web\ResponseInterface;
use Windwalker\Environment\Web\WebEnvironment;
use Windwalker\IO\Input;
use Windwalker\Registry\Registry;

/**
 * The WebApplication class.
 * 
 * @since  {DEPLOY_VERSION}
 */
class WebApplication extends AbstractWebApplication
{
	/**
	 * The application configuration object.
	 *
	 * @var    Registry
	 * @since  {DEPLOY_VERSION}
	 */
	public $config;

	/**
	 * Class constructor.
	 *
	 * @param   Input              $input        An optional argument to provide dependency injection for the application's
	 *                                           input object.  If the argument is a Input object that object will become
	 *                                           the application's input object, otherwise a default input object is created.
	 * @param   Registry           $config       An optional argument to provide dependency injection for the application's
	 *                                           config object.  If the argument is a Registry object that object will become
	 *                                           the application's config object, otherwise a default config object is created.
	 * @param   WebEnvironment     $environment  An optional argument to provide dependency injection for the application's
	 *                                           client object.  If the argument is a Web\WebEnvironment object that object will become
	 *                                           the application's client object, otherwise a default client object is created.
	 * @param   ResponseInterface  $response     The response object.
	 */
	public function __construct(Input $input = null, Registry $config = null, WebEnvironment $environment = null, ResponseInterface $response = null)
	{
		$this->environment = $environment instanceof WebEnvironment    ? $environment : new WebEnvironment;
		$this->response    = $response    instanceof ResponseInterface ? $response    : new Response;
		$this->input       = $input       instanceof Input             ? $input       : new Input;
		$this->config      = $config      instanceof Registry          ? $config      : new Registry;

		$this->initialise();

		// Set the execution datetime and timestamp;
		$this->set('execution.datetime', gmdate('Y-m-d H:i:s'));
		$this->set('execution.timestamp', time());
	}

	/**
	 * Method to run the application routines.  Most likely you will want to instantiate a controller
	 * and execute it, or perform some sort of task directly.
	 *
	 * @return  void
	 *
	 * @since   {DEPLOY_VERSION}
	 */
	protected function doExecute()
	{
		// Nothing to do
	}
}
 