<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Application;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Windwalker\Application\AbstractWebApplication;
use Windwalker\Cache\Cache;
use Windwalker\Core;
use Windwalker\Core\Application\Middleware\AbstractWebMiddleware;
use Windwalker\Core\Config\Config;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Database\Driver\AbstractDatabaseDriver;
use Windwalker\DI\ClassMeta;
use Windwalker\DI\Container;
use Windwalker\DI\ContainerAwareInterface;
use Windwalker\DI\ContainerAwareTrait;
use Windwalker\Environment\WebEnvironment;
use Windwalker\Event\DispatcherAwareInterface;
use Windwalker\Event\DispatcherAwareTrait;
use Windwalker\Filesystem\File;
use Windwalker\IO\PsrInput;
use Windwalker\Language\Language;
use Windwalker\Middleware\Chain\Psr7ChainBuilder;
use Windwalker\Middleware\Psr7InvokableInterface;
use Windwalker\Middleware\Psr7Middleware;
use Windwalker\Queue\Queue;
use Windwalker\Router\Exception\RouteNotFoundException;
use Windwalker\Session\Session;
use Windwalker\Uri\UriData;
use Windwalker\Utilities\Queue\PriorityQueue;

/**
 * The WebApplication class.
 *
 * @property-read  Config                        config
 * @property-read  Container                     container
 * @property-read  Core\Logger\LoggerManager     logger
 * @property-read  PsrInput                      input
 * @property-read  UriData                       uri
 * @property-read  Core\Event\EventDispatcher    dispatcher
 * @property-read  AbstractDatabaseDriver        database
 * @property-read  Core\Router\MainRouter        router
 * @property-read  Language                      language
 * @property-read  Core\Renderer\RendererManager renderer
 * @property-read  Core\Cache\cacheManager       cacheManager
 * @property-read  Cache                         cache
 * @property-read  Session                       session
 * @property-read  Core\Mailer\MailerManager     mailer
 * @property-read  Core\Asset\AssetManager       asset
 * @property-read  Core\User\UserManager         user
 * @property-read  Core\Security\CsrfGuard       csrf
 * @property-read  Core\Package\PackageResolver  packageResolver
 * @property-read  Queue                         queue
 *
 * @since  2.0
 */
class WebApplication extends AbstractWebApplication implements
    WindwalkerApplicationInterface,
    DispatcherAwareInterface,
    ContainerAwareInterface
{
    use Core\WindwalkerTrait;
    use Core\Utilities\Classes\BootableTrait;
    use DispatcherAwareTrait;
    use ContainerAwareTrait;

    /**
     * Property name.
     *
     * @var  string
     */
    protected $name = 'web';

    /**
     * Property mode.
     *
     * @var  string
     */
    protected $mode;

    /**
     * Property configPath.
     *
     * @var  string
     */
    protected $rootPath;

    /**
     * Property middlewares.
     *
     * @var  Psr7ChainBuilder
     */
    protected $middlewares;

    /**
     * Property router.
     *
     * @var  Core\Router\MainRouter
     */
    protected $router;

    /**
     * Class constructor.
     *
     * @param   Request        $request       An optional argument to provide dependency injection for the Http request
     *                                        object.
     * @param   Config         $config        An optional argument to provide dependency injection for the
     *                                        application's
     *                                        config object.
     * @param   WebEnvironment $environment   An optional argument to provide dependency injection for the
     *                                        application's
     *                                        environment object.
     *
     * @since   2.0
     */
    public function __construct(Request $request = null, Config $config = null, WebEnvironment $environment = null)
    {
        $this->config   = $config instanceof Config ? $config : new Config();
        $this->name     = $this->config->get('name', $this->name);
        $this->rootPath = $this->config->get('path.root', $this->rootPath);

        Core\Ioc::setProfile($this->name);

        $this->container = Core\Ioc::factory();

        parent::__construct($request, $this->config, $environment);

        $this->set('execution.start', microtime(true));
        $this->set('execution.memory', memory_get_usage());
    }

    /**
     * Custom initialisation method.
     *
     * Called at the end of the AbstractApplication::__construct() method.
     * This is for developers to inject initialisation code for their application classes.
     *
     * @return  void
     *
     * @since   2.0
     */
    protected function init()
    {
    }

    /**
     * Execute the application.
     *
     * @return  Response
     *
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     * @since   2.0
     */
    public function execute()
    {
        $this->boot();

        $this->triggerEvent('onAfterBoot', ['app' => $this]);

        $this->registerMiddlewares();

        $this->triggerEvent('onAfterRegisterMiddlewares', ['app' => $this, 'middlewares' => $this->getMiddlewares()]);

        $this->prepareExecute();

        // @event onBeforeExecute
        $this->triggerEvent('onBeforeExecute', ['app' => $this]);

        // Perform application routines.
        $response = $this->doExecute();

        // @event onAfterExecute
        $this->triggerEvent('onAfterExecute', ['app' => $this]);

        $this->postExecute();

        // @event onBeforeRespond
        $event = $this->dispatcher->triggerEvent('onBeforeRespond', ['app' => $this, 'response' => $response]);

        $response = $event['response'];

        $this->server->getOutput()->respond($response, $this->get('output.return_body', false));

        // @event onAfterRespond
        $this->triggerEvent('onAfterRespond', ['app' => $this, 'response' => $response]);

        return $response;
    }

    /**
     * Method to run the application routines. Most likely you will want to instantiate a controller
     * and execute it, or perform some sort of task directly.
     *
     * @return  Response
     *
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     * @since   2.0
     */
    protected function doExecute()
    {
        $chain = $this->getMiddlewareChain()->setEndMiddleware([$this, 'dispatch']);

        $this->server->setHandler($chain);

        return $this->server->execute($this->getFinalHandler());
    }

    /**
     * Method as the Psr7 WebHttpServer handler.
     *
     * @param  Request  $request      The Psr7 ServerRequest to get request params.
     * @param  Response $response     The Psr7 Response interface to prepare respond data.
     * @param  callable $finalHandler The next handler to support middleware pattern.
     *
     * @return  Response  The returned response object.
     *
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     * @since   3.0
     */
    public function dispatch(Request $request, Response $response, $finalHandler = null)
    {
        /** @var AbstractPackage $package */
        $package = $this->getPackage();

        if (!$package) {
            throw new RouteNotFoundException('Can not find package to execute.', 404);
        }

        return $package->execute($request->getAttribute('_controller'), $request, $response);
    }

    /**
     * getRouter
     *
     * @param bool $new
     *
     * @return  Core\Router\MainRouter
     */
    public function getRouter($new = false)
    {
        if (!$this->router || $new) {
            /** @var Core\Router\MainRouter $router */
            $router = $this->container->get('router');

            $files = (array) $this->get('routing.files');

            foreach ($files as $file) {
                if (File::getExtension($file) === 'php') {
                    $router->register($file);
                } else {
                    $routes = $router::loadRoutingFile($file);

                    $router->registerRawRouting($routes, $this->container->get('package.resolver'));
                }
            }

            $this->router = $router;
        }

        return $this->router;
    }

    /**
     * bootRouting
     *
     * @param bool $refresh
     *
     * @return  static
     */
    public function bootRouting($refresh = false)
    {
        $this->getRouter($refresh);

        return $this;
    }

    /**
     * setCurrentPackage
     *
     * @param   string|AbstractPackage $package
     *
     * @return  static
     */
    public function setCurrentPackage($package)
    {
        if (!$package instanceof AbstractPackage) {
            $package = $this->packageResolver->getPackage($package);
        }

        $this->packageResolver->setCurrentPackage($package);

        return $this;
    }

    /**
     * setTask
     *
     * @param   string|AbstractPackage                    $package
     * @param   string|Core\Controller\AbstractController $controller
     *
     * @return  static
     */
    public function setTask($package, $controller)
    {
        if (!$package instanceof AbstractPackage) {
            $package = $this->getPackage($package);
        }

        $this->setCurrentPackage($package);
        $this->server->setRequest($this->request->withAttribute('_controller', $controller));

        return $this;
    }

    /**
     * registerMiddlewares
     *
     * @return  void
     */
    protected function registerMiddlewares()
    {
        // Init middlewares
        $middlewares = (array) $this->config->get('middlewares', []);

        foreach ($middlewares as $k => $middleware) {
            $this->addMiddleware($middleware, is_numeric($k) ? $k : PriorityQueue::NORMAL);
        }

        // Remove closures
        $this->config->set('middlewares', null);
    }

    /**
     * getMiddlewareChain
     *
     * @return  Psr7ChainBuilder
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     */
    public function getMiddlewareChain()
    {
        $middlewares = array_reverse(iterator_to_array(clone $this->getMiddlewares()));

        $chain = new Psr7ChainBuilder();

        foreach ($middlewares as $middleware) {
            $data = [];

            if (is_array($middleware)) {
                $data = $middleware;
                $middleware = $data['middleware'];

                unset($data['middleware']);
            }

            if ($middleware instanceof ClassMeta
                || (is_string($middleware) && is_subclass_of($middleware, AbstractWebMiddleware::class))) {
                $middleware = new Psr7Middleware($this->container->newInstance($middleware, $data));
            } elseif ($middleware instanceof \Closure) {
                $middleware->bindTo($this);
            } elseif ($middleware === false) {
                continue;
            }

            $chain->add($middleware);
        }

        return $chain;
    }

    /**
     * Method to get property Middlewares
     *
     * @return  PriorityQueue
     */
    public function getMiddlewares()
    {
        if (!$this->middlewares) {
            $this->middlewares = new PriorityQueue();
        }

        return $this->middlewares;
    }

    /**
     * Method to set property middlewares
     *
     * @param   PriorityQueue $middlewares
     *
     * @return  static  Return self to support chaining.
     */
    public function setMiddlewares(PriorityQueue $middlewares)
    {
        $this->middlewares = $middlewares;

        return $this;
    }

    /**
     * addMiddleware
     *
     * @param   callable|Psr7InvokableInterface $middleware
     * @param   int                             $priority
     *
     * @return static
     */
    public function addMiddleware($middleware, $priority = PriorityQueue::NORMAL)
    {
        $this->getMiddlewares()->insert($middleware, $priority);

        return $this;
    }

    /**
     * addMessage
     *
     * @param string|array $messages
     * @param string       $type
     *
     * @return  static
     */
    public function addMessage($messages, $type = 'info')
    {
        $this->session->getFlashBag()->add($messages, $type);

        return $this;
    }

    /**
     * getMessages
     *
     * @param bool $clear
     *
     * @return  array
     */
    public function getMessages($clear = false)
    {
        if ($clear) {
            return $this->session->getFlashBag()->takeAll();
        }

        return $this->session->getFlashBag()->all();
    }

    /**
     * clearMessage
     *
     * @return  static
     */
    public function clearMessages()
    {
        $this->session->getFlashBag()->clear();

        return $this;
    }

    /**
     * Get the logger.
     *
     * @return  Core\Logger\LoggerManager
     *
     * @since   2.0
     */
    public function getLogger()
    {
        return $this->container->get('logger');
    }

    /**
     * Redirect to another URL.
     *
     * If the headers have not been sent the redirect will be accomplished using a "301 Moved Permanently"
     * or "303 See Other" code in the header pointing to the new location. If the headers have already been
     * sent this will be accomplished using a JavaScript statement.
     *
     * @param   string      $url  The URL to redirect to. Can only be http/https URL
     * @param   boolean|int $code True if the page is 301 Permanently Moved, otherwise 303 See Other is assumed.
     *
     * @return  void
     *
     * @since   2.0
     */
    public function redirect($url, $code = 303)
    {
        $this->triggerEvent('onBeforeRedirect', [
            'app' => $this,
            'url' => &$url,
            'code' => &$code,
        ]);

        parent::redirect($url, $code);
    }

    /**
     * Method to get property Mode
     *
     * @return  string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Method to set property mode
     *
     * @param   string $mode
     *
     * @return  static  Return self to support chaining.
     */
    public function setMode($mode)
    {
        $this->mode = $mode;

        return $this;
    }

    /**
     * is utilized for reading data from inaccessible members.
     *
     * @param   $name  string
     *
     * @return  mixed
     */
    public function __get($name)
    {
        $diMapping = [
            'input' => 'input',
            'uri' => 'uri',
            'dispatcher' => 'dispatcher',
            'database' => 'database',
            'language' => 'language',
            'renderer' => 'renderer',
            'cache' => 'cache',
            'cacheManager' => 'cache.manager',
            'session' => 'session',
            'mailer' => 'mailer',
            'asset' => 'asset',
            'user' => 'user.manager',
            'csrf' => 'security.csrf',
            'packageResolver' => 'package.resolver',
            'queue' => 'queue',
        ];

        if (isset($diMapping[$name])) {
            return $this->container->get($diMapping[$name]);
        }

        $allowNames = [
            'container',
        ];

        if (in_array($name, $allowNames)) {
            return $this->$name;
        }

        if ($name === 'router') {
            return $this->getRouter();
        }

        if ($name === 'logger') {
            return $this->getLogger();
        }

        return parent::__get($name);
    }
}
