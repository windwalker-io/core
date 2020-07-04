<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Controller\Middleware\AbstractControllerMiddleware;
use Windwalker\Core\Controller\Middleware\ControllerData;
use Windwalker\Core\Frontend\Bootstrap;
use Windwalker\Core\Ioc;
use Windwalker\Core\Mvc\ModelResolver;
use Windwalker\Core\Mvc\MvcHelper;
use Windwalker\Core\Mvc\ViewResolver;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\DefaultPackage;
use Windwalker\Core\Package\NullPackage;
use Windwalker\Core\Package\PackageHelper;
use Windwalker\Core\Repository\Exception\ValidateFailException;
use Windwalker\Core\Repository\Repository;
use Windwalker\Core\Router\PackageRouter;
use Windwalker\Core\Router\RouteString;
use Windwalker\Core\Utilities\Classes\BootableTrait;
use Windwalker\Core\View\AbstractView;
use Windwalker\Core\View\HtmlView;
use Windwalker\Core\View\LayoutRenderableInterface;
use Windwalker\DI\Container;
use Windwalker\Event\EventInterface;
use Windwalker\Event\EventTriggerableInterface;
use Windwalker\Http\Helper\HeaderHelper;
use Windwalker\Http\Response\RedirectResponse;
use Windwalker\Http\Response\Response;
use Windwalker\IO\Input;
use Windwalker\Middleware\Chain\ChainBuilder;
use Windwalker\Structure\Structure;
use Windwalker\Utilities\Queue\PriorityQueue;
use Windwalker\Utilities\Reflection\ReflectionHelper;

/**
 * The Controller class.
 *
 * @property-read  Structure      $config  Config object.
 * @property-read  WebApplication $app     The application object.
 * @property-read  Input          $input   The input object.
 * @property-read  PackageRouter  $router  Router of this package.
 *
 * @since  2.0
 */
abstract class AbstractController implements EventTriggerableInterface, \Serializable
{
    use BootableTrait;

    /**
     * Controller name (Name of this MVC group).
     *
     * @var  string
     */
    protected $name;

    /**
     * The request input object.
     *
     * @var  Input
     */
    protected $input;

    /**
     * Application object.
     *
     * @var  WebApplication
     */
    protected $app;

    /**
     * DI Container.
     *
     * @var  Container
     */
    protected $container;

    /**
     * Package object.
     *
     * @var  AbstractPackage
     */
    protected $package;

    /**
     * Config object.
     *
     * @var  Structure
     */
    protected $config;

    /**
     * If set to TRUE, all message will not set to session.
     *
     * @var  boolean
     */
    protected $mute = false;

    /**
     * If this controller in HMVC mode?.
     *
     * All redirect will disable in HMVC mode.
     *
     * @var  boolean
     */
    protected $hmvc = false;

    /**
     * Psr7 Server Request object.
     *
     * @var  ServerRequestInterface
     */
    protected $request;

    /**
     * Psr7 response object.
     *
     * @var  ResponseInterface
     */
    protected $response;

    /**
     * The controller middleware object.
     *
     * @var  AbstractControllerMiddleware[]|PriorityQueue
     */
    protected $middlewares = [];

    /**
     * Class init.
     */
    public function __construct()
    {
        $this->config = $this->getConfig();

        // Prepare middlewares queue
        $this->middlewares = (new PriorityQueue())->insertArray((array) $this->middlewares);

        // Boot all traits used
        $this->bootTraits($this);

        // Custom initialise code
        $this->init();
    }

    /**
     * Init this class.
     *
     * @return  void
     */
    protected function init()
    {
        // Override it if you need.
    }

    /**
     * Run HMVC to fetch content from other controller.
     *
     * @param string|AbstractController $task    The task to exeiocute, must be controller object or string.
     *                                           The string format is `Name\ActionController`
     *                                           example: `Page\GetController`
     * @param Input|array               $input   The input for this task, can be array or Input object.
     * @param string                    $package The package for this controller, can be string or AbstractPackage.
     *
     * @return mixed
     * @throws \Throwable
     */
    public function hmvc($task, $input = null, $package = null)
    {
        // If task is controller object, just execute it.
        if ($task instanceof AbstractController) {
            if (is_array($input)) {
                $input = new Input($input);
            }

            // Let's push some important data into it.
            /** @var AbstractController $controller */
            $controller = $task->setContainer($this->container)
                ->isHmvc(true)
                ->setRequest($this->request)
                ->setResponse($this->response)
                ->setPackage($this->package)
                ->setApplication($this->package->app)
                ->setInput($input);

            // Do action and return executed result.
            $result = $controller->execute();

            // Take back the redirect information.
            $this->passRedirect($controller);

            return $result;
        }

        // If task is string, find controller by package
        $package = $package ? $this->app->getPackage($package) : $this->package;

        $response = $package->execute(
            $package->getController($task, $input),
            $this->getRequest(),
            new Response(),
            true
        );

        // Take back the redirect information.
        $this->passRedirect($package->getCurrentController());

        return $response->getBody()->__toString();
    }

    /**
     * A hook before main process executing.
     *
     * @return  void
     */
    protected function prepareExecute()
    {
    }

    /**
     * The main execution process.
     *
     * @return  mixed
     */
    abstract protected function doExecute();

    /**
     * A hook after main process executing.
     *
     * @param mixed $result The result content to return, can be any value or boolean.
     *
     * @return  mixed
     */
    protected function postExecute($result = null)
    {
        return $result;
    }

    /**
     * Execute the controller.
     *
     * @return mixed Return executed result.
     *
     * @throws \Exception
     * @throws \Throwable
     */
    public function execute()
    {
        try {
            // @ prepare hook
            $this->prepareExecute();

            // @ before event
            $this->triggerEvent('onControllerBeforeExecute', [
                'controller' => $this
            ]);

            // Prepare the last middleware, the last middleware is the real logic of this controller self.
            $chain = $this->getMiddlewareChain()->setEndMiddleware(function () {
                return $this->container->call([$this, 'doExecute'], [], $this);
            });

            // Do execute, run middlewares.
            $result = $chain->execute(new ControllerData(get_object_vars($this)));

            // @ post hook
            $result = $this->postExecute($result);

            // @ post event
            $this->triggerEvent('onControllerAfterExecute', [
                'controller' => $this,
                'result' => &$result,
            ]);
        } catch (ValidateFailException $e) {
            return $this->processFailure($e);
        } catch (\Exception $e) {
            // You can do some error handling in processFailure(), for example: rollback the transaction.
            $result = $this->processFailure($e);

            if (!$this->app->get('system.debug')) {
                return $result;
            }

            throw $e;
        } catch (\Throwable $t) {
            // You can do some error handling in processFailure(), for example: rollback the transaction.
            $this->processFailure(new \ErrorException(
                $t->getMessage(),
                $t->getCode(),
                E_ERROR,
                $t->getFile(),
                $t->getLine(),
                $t
            ));

            throw $t;
        }

        if ($result === false) {
            // You can do some error handling in processFailure(), for example: rollback the transaction.
            return $this->processFailure(new \Exception('Unknown Error'));
        }

        // Now we return result to package that it will handle response.
        return $this->processSuccess($result);
    }

    /**
     * Method to easily distribute task to other methods that we can process different tasks.
     *
     * Example in doExecute():
     * ```
     * return $this->delegate($this->input->get('task'), `arg1`, `arg2`);
     *
     * // OR
     *
     * return $this->delegate([$object, 'method'], `arg1`, `arg2`);
     * ```
     *
     * @param   string $task The task name.
     * @param   array  $args The arguments we provides.
     *
     * @return mixed
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     */
    protected function delegate($task, ...$args)
    {
        if (method_exists($this, $task)) {
            return $this->container->call([$this, $task], $args, $this);
        }

        if (is_callable($task)) {
            return $this->container->call($task, $args, $this);
        }

        throw new \LogicException('Task: ' . $task . ' not found.');
    }

    /**
     * Method to easily render view.
     *
     * @param LayoutRenderableInterface|string $view   The view name or object.
     * @param string                           $layout The layout to render.
     * @param string                           $engine The engine of template.
     * @param array                            $data   The data to set in view.
     *
     * @return string
     * @throws \LogicException
     * @throws \Exception
     */
    public function renderView($view, $layout = 'default', $engine = 'php', array $data = [])
    {
        $viewObject = $view;

        if (\is_string($view)) {
            $viewObject = class_exists($view)
                ? $this->container->createSharedObject($view)
                : $this->getView($view, 'html', $engine);
        }

        $this->setConfig($this->config);

        // Set data into View
        foreach ($data as $key => $value) {
            $viewObject[$key] = $value;
        }

        if ($layout !== null && $viewObject instanceof LayoutRenderableInterface) {
            $viewObject->setLayout($layout);
        }

        return $viewObject->render();
    }

    /**
     * Process success.
     *
     * @param  mixed $result
     *
     * @return mixed
     */
    public function processSuccess($result)
    {
        return $result;
    }

    /**
     * Process failure.
     *
     * @param \Exception $e
     *
     * @return bool
     * @throws \Exception
     */
    public function processFailure(\Exception $e = null)
    {
        throw $e;

        return false;
    }

    /**
     * Get view object.
     *
     * @param string $name     The view name.
     * @param string $format   The view foramt.
     * @param string $engine   The renderer template engine.
     * @param bool   $forceNew The Force create new instance.
     *
     * @return AbstractView|HtmlView
     * @throws \Exception
     */
    public function getView($name = null, $format = 'html', $engine = null, $forceNew = false)
    {
        $name = $name ?: $this->getName();

        $format = $format ?: 'html';

        $key = ViewResolver::getDIKey($name . '.' . strtolower($format));

        // Let's prepare controller getter.
        if (!$this->container->exists($key)) {
            $this->container->share($key, function (Container $container) use ($name, $format, $engine) {
                // Find if package exists
                $viewName = sprintf('%s\%s%sView', ucfirst($name), ucfirst($name), ucfirst($format));

                $config = clone $this->config;

                /*
                 * If the name of this view not same as this controller, we don't pass name into it,
                 * so that the view will keep it's own name.
                 * Otherwise we override the name in view config with ours.
                 */
                if ($name && strcasecmp($name, $this->getName()) !== 0) {
                    $config['name'] = null;
                }

                try {
                    // Use MvcResolver to find view class.
                    $class = $this->getPackage()->getMvcResolver()->getViewResolver()->resolve($viewName);

                    // Create object by container, container will auto inject all necessary dependencies
                    return $container->createSharedObject($class, ['renderer' => $engine, 'config' => $config]);
                } catch (\Exception $e) {
                    if (!$e instanceof \DomainException && !$e instanceof \UnexpectedValueException) {
                        throw $e;
                    }

                    // Guess the view position
                    $class = MvcHelper::getPackageNamespace($this) . '\View\\' . ucfirst($viewName);

                    if (class_exists($class)) {
                        return $container->createSharedObject($class, ['renderer' => $engine, 'config' => $config]);
                    }

                    // If format is html or NULL, we return HtmlView as default.
                    if (strtolower($format) === 'html') {
                        $config['name'] = $name;

                        return new HtmlView([], $config, $engine);
                    }

                    // Otherwise we throw exception to notice developers that they did something wrong.
                    throw $e;
                }
            });
        }

        // Get view from controller.
        return $this->container->get($key, $forceNew);
    }

    /**
     * getRepository
     *
     * @param string $name
     * @param mixed  $source
     * @param bool   $forceNew
     *
     * @return Repository
     * @throws \Exception
     *
     * @since  3.2
     */
    public function getRepository($name = null, $source = null, $forceNew = false)
    {
        $name = $name ?: $this->getName();

        $key = ModelResolver::getDIKey($name);

        // Let's prepare controller getter.
        if (!$this->container->exists($key)) {
            // Use resolver to find model class and create it.
            $this->container->share($key, function (Container $container) use ($name, $source) {
                $config    = clone $this->config;
                $modelName = ucfirst($name) . 'Model';
                $repoName  = ucfirst($name) . 'Repository';
                $source    = $source ?: $this->getDataSource();

                /*
                 * If the name of this model not same as this controller, we don't pass name into it,
                 * so that the model will keep it's own name.
                 * Otherwise we override the name in model config with ours.
                 */
                if ($name && strcasecmp($name, $this->getName()) !== 0) {
                    $config['name'] = null;
                }

                try {
                    $repoResolver = $this->getPackage()->getMvcResolver()->getRepositoryResolver();

                    // Use RepositoryResolver to fin Repository class
                    try {
                        $class = $repoResolver->resolve($repoName);
                    } catch (\DomainException $e) {
                        // Use ModelResolver to get legacy Model classes
                        // @deprecated
                        $class = $repoResolver->getModelResolver()->resolve($modelName);
                    }

                    // Create object by container, container will auto inject all necessary dependencies
                    return $container->createSharedObject($class, ['config' => $config]);
                } catch (\Exception $e) {
                    if (!$e instanceof \DomainException && !$e instanceof \UnexpectedValueException) {
                        throw $e;
                    }

                    // Guess the repository position
                    $class = MvcHelper::getPackageNamespace($this) . '\Repository\\' . ucfirst($repoName);

                    if (class_exists($class)) {
                        return $container->createSharedObject($class, ['source' => $source, 'config' => $config]);
                    }

                    // Guess the model position
                    // @deprecated
                    $class = MvcHelper::getPackageNamespace($this) . '\Model\\' . ucfirst($modelName);

                    if (class_exists($class)) {
                        return $container->createSharedObject($class, ['source' => $source, 'config' => $config]);
                    }

                    $config['name'] = $name;

                    return new Repository($config);
                }
            });
        }

        // Get model from controller.
        return $this->container->get($key, $forceNew);
    }

    /**
     * getRepository
     *
     * @param string $name
     * @param mixed  $source
     * @param bool   $forceNew
     *
     * @return Repository
     * @throws \Exception
     *
     * @deprecated Use getRepository() instead.
     */
    public function getModel($name = null, $source = null, $forceNew = false)
    {
        return $this->getRepository($name, $source, $forceNew);
    }

    /**
     * setRedirect
     *
     * @param string $url
     * @param int    $code
     * @param array  $headers
     * @param bool   $allowOutside
     *
     * @return static
     */
    public function setRedirect($url, $code = 303, array $headers = [], $allowOutside = false)
    {
        if (!$allowOutside) {
            $url = $this->validRedirectUrl($url);
        }

        if (is_stringable($url)) {
            $url = (string) $url;
        }

        $this->response = new RedirectResponse($url, $code, $headers ?: $this->response->getHeaders());

        return $this;
    }

    /**
     * redirect
     *
     * @param string $url
     * @param int    $code
     * @param bool   $allowOutside
     */
    public function redirect($url, $code = 303, $allowOutside = false)
    {
        if ($this->isHmvc() || !$this->app) {
            return;
        }

        if (!$allowOutside) {
            $url = $this->validRedirectUrl($url);
        }

        if (is_stringable($url)) {
            $url = (string) $url;
        }

        $this->app->redirect($url, $code);
    }

    /**
     * to
     *
     * @param string $route
     * @param array  $queries
     * @param array  $config
     *
     * @return  RouteString
     *
     * @since  __DEPLOY_VERSION__
     */
    public function to(string $route, array $queries = [], array $config = []): RouteString
    {
        return $this->router->to($route, $queries, $config);
    }

    /**
     * redirectTo
     *
     * @param string $route
     * @param array  $queries
     * @param array  $config
     * @param int    $code
     *
     * @return  void
     *
     * @since  __DEPLOY_VERSION__
     */
    public function redirectTo(string $route, array $queries = [], array $config = [], int $code = 303): void
    {
        $this->redirect(
            $this->router->to($route, $queries, $config),
            $code
        );
    }

    /**
     * validRedirectUrl
     *
     * @param string $url
     *
     * @return  string
     *
     * @since  3.5
     */
    public function validRedirectUrl($url)
    {
        $root = $this->app->uri->root;

        if (strpos($url, '/') === 0) {
            return $url;
        }

        if (stripos($url, $root) !== 0) {
            return $root;
        }

        return $url;
    }

    /**
     * passRedirect
     *
     * @param AbstractController $controller
     *
     * @return  static
     */
    public function passRedirect(AbstractController $controller)
    {
        $response = $controller->getResponse();

        if ($response instanceof RedirectResponse) {
            $this->response = $response;
        }

        return $this;
    }

    /**
     * header
     *
     * @param string       $name  Header name.
     * @param string|array $value Header value.
     * @param bool         $emit  Emit header immediately
     *
     * @return  static
     */
    public function header($name, $value, $emit = false)
    {
        if ($emit) {
            $name = HeaderHelper::normalizeHeaderName($name);

            foreach ((array) $value as $v) {
                header("$name: $v", false);
            }
        } else {
            $this->response = $this->response->withAddedHeader($name, $value);
        }

        return $this;
    }

    /**
     * addMessage
     *
     * @param string $messages
     * @param string $type
     *
     * @return  static
     */
    public function addMessage($messages, $type = Bootstrap::MSG_INFO)
    {
        if (!$this->mute) {
            $this->app->addMessage($messages, $type);
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

    /**
     * Method to get property Package
     *
     * @param int $backwards
     *
     * @return AbstractPackage
     * @throws \ReflectionException
     */
    public function getPackage($backwards = 4)
    {
        if (!$this->package || $this->package instanceof DefaultPackage) {
            $package = null;

            // Guess package name.
            $name = MvcHelper::guessPackage(get_called_class(), $backwards);

            // Get package object.
            if ($name) {
                $package = PackageHelper::getPackage(strtolower($name));
            }

            // If name not found, find class.
            if (!$package) {
                $packages = PackageHelper::getPackages();

                foreach ($packages as $pkgObject) {
                    $packageClass = ReflectionHelper::getShortName($pkgObject);

                    if (strpos($packageClass, ucfirst($name)) === 0) {
                        $package = $pkgObject;

                        break;
                    }
                }
            }

            // If package not found, use NullPackage instead.
            if (!$package) {
                $ref     = new \ReflectionClass($this);
                $package = new NullPackage();

                $package->setName($name);
                $package->dir = realpath(dirname($ref->getFileName()) . str_repeat('/..', $backwards - 2));
            }

            $this->setPackage($package);
        }

        return $this->package;
    }

    /**
     * Method to set property package
     *
     * @param   AbstractPackage $package
     *
     * @return  static  Return self to support chaining.
     * @throws \ReflectionException
     */
    public function setPackage(AbstractPackage $package)
    {
        $this->config['name']         = $this->getName();
        $this->config['package.name'] = $package->getName();
        $this->config['package.path'] = $package->getDir();

        $this->package = $package;

        return $this;
    }

    /**
     * registerMiddlewares
     *
     * @return  ChainBuilder
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     */
    protected function getMiddlewareChain()
    {
        $middlewares = array_reverse(iterator_to_array(clone $this->middlewares));

        $chain = new ChainBuilder();

        foreach ($middlewares as $middleware) {
            // If is class name, just create it.
            if (is_string($middleware) && is_subclass_of($middleware, AbstractControllerMiddleware::class)) {
                $middleware = $this->container->newInstance($middleware, ['controller' => $this]);
            } elseif ($middleware instanceof \Closure) {
                // If is closure, we bind $this to current object
                $middleware->bindTo($this);
            }

            $chain->add($middleware);
        }

        return $chain;
    }

    /**
     * Method to get property Container
     *
     * @return  Container
     * @throws \ReflectionException
     */
    public function getContainer()
    {
        if (!$this->container) {
            $package = $this->getPackage();

            $this->container = $package->getContainer() ?: Ioc::factory();
        }

        return $this->container;
    }

    /**
     * Method to set property container
     *
     * @param   Container $container
     *
     * @return  static  Return self to support chaining.
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * getApplication
     *
     * @return  WebApplication
     * @throws \UnexpectedValueException
     * @throws \ReflectionException
     */
    public function getApplication()
    {
        if (!$this->app) {
            $this->app = $this->getPackage()->app ?: $this->container->get('application');
        }

        return $this->app;
    }

    /**
     * setApplication
     *
     * @param WebApplication $app
     *
     * @return  static
     */
    public function setApplication(WebApplication $app)
    {
        $this->app = $app;

        return $this;
    }

    /**
     * getInput
     *
     * @return  Input
     */
    public function getInput()
    {
        if (!$this->input) {
            $this->input = new Input();
        }

        return $this->input;
    }

    /**
     * Method to set property input
     *
     * @param   Input $input
     *
     * @return  static  Return self to support chaining.
     */
    public function setInput(Input $input)
    {
        $this->input = $input;

        return $this;
    }

    /**
     * Method to get property Name
     *
     * @param integer $backwards
     *
     * @return string
     */
    public function getName($backwards = 2)
    {
        if (!$this->name) {
            $this->name = MvcHelper::guessName(static::class, $backwards);
        }

        return $this->name;
    }

    /**
     * Method to set property name
     *
     * @param   string $name
     *
     * @return  static  Return self to support chaining.
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Method to get property Config
     *
     * @return  Structure
     */
    public function getConfig()
    {
        if (!$this->config || !$this->config instanceof Structure) {
            $this->config = new Structure($this->config);
        }

        return $this->config;
    }

    /**
     * Method to set property config
     *
     * @param   Structure $config
     *
     * @return  static  Return self to support chaining.
     */
    public function setConfig(Structure $config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * getRouter
     *
     * @return  \Windwalker\Core\Router\PackageRouter
     */
    public function getRouter()
    {
        return $this->package->router;
    }

    /**
     * Trigger an event.
     *
     * @param   EventInterface|string $event The event object or name.
     * @param   array                 $args  The arguments to set in event.
     *
     * @return  EventInterface  The event after being passed through all listeners.
     * @throws \UnexpectedValueException
     *
     * @since   2.0
     * @throws \ReflectionException
     */
    public function triggerEvent($event, $args = [])
    {
        $container = $this->getContainer();

        if (!$container->exists('dispatcher')) {
            return null;
        }

        $dispatcher = $container->get('dispatcher');

        return $dispatcher->triggerEvent($event, $args);
    }

    /**
     * Check this controller is in HMVC that we can close some behaviors.
     *
     * @param   boolean $boolean
     *
     * @return  static|boolean
     */
    public function isHmvc($boolean = null)
    {
        if ($boolean === null) {
            return $this->hmvc;
        }

        $this->hmvc = (bool) $boolean;

        return $this;
    }

    /**
     * Method to get property Request
     *
     * @return  ServerRequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Method to set property request
     *
     * @param   ServerRequestInterface $request
     *
     * @return  static  Return self to support chaining.
     */
    public function setRequest($request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Method to get property Response
     *
     * @return  ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Method to set property response
     *
     * @param   ResponseInterface $response
     *
     * @return  static  Return self to support chaining.
     */
    public function setResponse($response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * getDataSource
     *
     * @param string $key
     *
     * @return  mixed
     */
    public function getDataSource($key = 'database')
    {
        if ($this->container->exists('database')) {
            return $this->container->get($key);
        }

        return null;
    }

    /**
     * addMiddleware
     *
     * @param   callable|AbstractControllerMiddleware $middleware
     * @param   int                                   $priority
     *
     * @return static
     */
    public function addMiddleware($middleware, $priority = PriorityQueue::NORMAL)
    {
        $this->middlewares->insert($middleware, $priority);

        return $this;
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
     * __get
     *
     * @param   string $name
     *
     * @return  mixed
     *
     * @throws \OutOfRangeException
     */
    public function __get($name)
    {
        if ($name === 'input') {
            return $this->input;
        }

        if ($name === 'app' || $name === 'application') {
            return $this->app;
        }

        if ($name === 'config') {
            return $this->config;
        }

        if ($name === 'router') {
            return $this->getRouter();
        }

        throw new \OutOfRangeException('Property: ' . $name . ' not exists.');
    }

    /**
     * Serialize the controller.
     *
     * @return  string  The serialized controller.
     *
     * @since   2.0
     */
    public function serialize()
    {
        return serialize($this->getInput());
    }

    /**
     * Unserialize the controller.
     *
     * @param   string $input The serialized controller.
     *
     * @return  AbstractController  Returns itself to support chaining.
     */
    public function unserialize($input)
    {
        $input = unserialize($input);

        $this->setInput($input);

        return $this;
    }
}
