<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\View;

use Windwalker\Core\Mvc\MvcHelper;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\NullPackage;
use Windwalker\Core\Package\PackageHelper;
use Windwalker\Core\Repository\Repository;
use Windwalker\Core\Router\PackageRouter;
use Windwalker\Core\Utilities\Classes\BootableTrait;
use Windwalker\Data\Data;
use Windwalker\String\StringNormalise;
use Windwalker\Structure\Structure;

/**
 * The AbstractView class.
 *
 * @property-read  ViewModel|mixed $repository   The ViewModel object.
 * @property-read  ViewModel|mixed $model        The ViewModel object.
 * @property-read  Structure       $config       Config object.
 * @property-read  PackageRouter   $router       Router object.
 *
 * @since  2.1.5.3
 */
abstract class AbstractView implements \ArrayAccess
{
    use BootableTrait;

    /**
     * @const boolean
     */
    const DEFAULT_MODEL = true;

    /**
     * Property name.
     *
     * @var  string
     */
    protected $name = null;

    /**
     * Property data.
     *
     * @var  array
     */
    protected $data;

    /**
     * Property package.
     *
     * @var  AbstractPackage
     */
    protected $package;

    /**
     * Property config.
     *
     * @var Structure
     */
    protected $config;

    /**
     * Property model.
     *
     * @var ViewModel
     */
    protected $repository;

    /**
     * Property booted.
     *
     * @var  boolean
     */
    protected $booted = false;

    /**
     * Method to instantiate the view.
     *
     * @param   array $data   The data array.
     * @param   array $config The view config.
     */
    public function __construct($data = null, $config = null)
    {
        $this->config     = $config instanceof Structure ? $config : new Structure($config);
        $this->repository = new ViewModel();

        $this->setData($data);

        $this->bootTraits($this);

        $this->init();
    }

    /**
     * init
     *
     * @return  void
     */
    protected function init()
    {
    }

    /**
     * boot
     *
     * @return  void
     */
    public function boot()
    {
    }

    /**
     * prepareRender
     *
     * @param   Data $data
     *
     * @return  void
     */
    protected function prepareRender($data)
    {
    }

    /**
     * prepareData
     *
     * @param \Windwalker\Data\Data $data
     *
     * @return  void
     */
    protected function prepareData($data)
    {
    }

    /**
     * handleData
     *
     * @return  static
     */
    public function handleData()
    {
        $data = $this->getData();

        $this->prepareRender($data);

        $this->prepareData($data);

        $this->prepareGlobals($data);

        $dispatcher = $this->getPackage()->getDispatcher();

        $dispatcher->triggerEvent('onViewAfterHandleData', [
            'data' => &$data,
            'view' => $this
        ]);

        return $this;
    }

    /**
     * processData
     *
     * @return  mixed
     */
    public function getHandledData()
    {
        return $this->handleData()->getData();
    }

    /**
     * getData
     *
     * @return  \Windwalker\Data\Data
     */
    public function getData()
    {
        if (!$this->data) {
            $this->data = new Data();
        }

        return $this->data;
    }

    /**
     * setData
     *
     * @param   array $data
     *
     * @return  static  Return self to support chaining.
     */
    public function setData($data)
    {
        $this->data = $data instanceof Data ? $data : new Data($data);

        return $this;
    }

    /**
     * get
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return  null
     */
    public function get($key, $default = null)
    {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }

    /**
     * set
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return  $this
     */
    public function set($key, $value)
    {
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * render
     *
     * @return  string
     *
     * @throws \RuntimeException
     */
    public function render()
    {
        $this->handleData();

        $data = $this->getData();

        $dispatcher = $this->getPackage()->getDispatcher();

        $dispatcher->triggerEvent('onViewBeforeRender', [
            'data' => $this->data,
            'view' => $this
        ]);

        $output = $this->doRender($data);

        $output = $this->postRender($output);

        $dispatcher->triggerEvent('onViewAfterRender', [
            'data' => $this->data,
            'view' => $this,
            'output' => &$output
        ]);

        return $output;
    }

    /**
     * doRender
     *
     * @param  Data $data
     *
     * @return string
     */
    abstract protected function doRender($data);

    /**
     * postRender
     *
     * @param   string $output
     *
     * @return  string
     */
    protected function postRender($output)
    {
        return $output;
    }

    /**
     * __toString
     *
     * @return  string
     */
    public function __toString()
    {
        try {
            return (string) $this->render();
        } catch (\Exception $e) {
            trigger_error($e->getMessage(), E_USER_ERROR);
        } catch (\Throwable $e) {
            trigger_error($e->getMessage(), E_USER_ERROR);
        }

        return (string) $e;
    }

    /**
     * Is a property exists or not.
     *
     * @param mixed $offset Offset key.
     *
     * @return  boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * Get a property.
     *
     * @param mixed $offset Offset key.
     *
     * @throws  \InvalidArgumentException
     * @return  mixed The value to return.
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Set a value to property.
     *
     * @param mixed $offset Offset key.
     * @param mixed $value  The value to set.
     *
     * @throws  \InvalidArgumentException
     * @return  void
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * Unset a property.
     *
     * @param mixed $offset Offset key to unset.
     *
     * @throws  \InvalidArgumentException
     * @return  void
     */
    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->data[$offset]);
        }
    }

    /**
     * getName
     *
     * @param int $backwards
     *
     * @return string
     */
    public function getName($backwards = 2)
    {
        if (!$this->name) {
            if ($this->config['name']) {
                return $this->name = $this->config['name'];
            }

            $class = static::class;

            // If we are using this class as default view, return default name.
            if ($class === HtmlView::class) {
                return $this->name = 'default';
            }

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
     * Method to get property Package
     *
     * @param int $backwards
     *
     * @return AbstractPackage
     */
    public function getPackage($backwards = 4)
    {
        if (!$this->package) {
            // Get package name or guess it.
            $name = $this->config['package.name'] ?: MvcHelper::guessPackage(get_called_class(), $backwards);

            // Get package object
            if ($name) {
                $this->package = PackageHelper::getPackage($name);
            }

            // If package not found, use NullPackage instead.
            if (!$this->package) {
                $this->package = new NullPackage();

                $this->package->setName($name);
            }
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
        $this->package = $package;

        $this->config['package.name'] = $package->getName();
        $this->config['package.path'] = $package->getDir();

        return $this;
    }

    /**
     * prepareGlobals
     *
     * @param \Windwalker\Data\Data $data
     *
     * @return  void
     */
    protected function prepareGlobals($data)
    {
    }

    /**
     * Method to get property Config
     *
     * @return  Structure
     */
    public function getConfig()
    {
        if (!$this->config) {
            $this->config = new Structure();
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
    public function setConfig($config)
    {
        $this->config = $config instanceof Structure ? $config : new Structure($config);

        $this->name    = $this->config['name'];
        $this->package = $this->getPackage();

        return $this;
    }

    /**
     * __get
     *
     * @param string $name
     *
     * @return  mixed
     */
    public function __get($name)
    {
        if ($name === 'config') {
            return $this->config;
        }

        if ($name === 'model' || $name === 'repository') {
            return $this->repository;
        }

        if ($name === 'router') {
            return $this->getRouter();
        }

        return null;
    }

    /**
     * Method to get property Model
     *
     * @param  string $name
     *
     * @return Repository
     */
    public function getRepository($name = null)
    {
        return $this->repository->getRepository($name);
    }

    /**
     * Method to set property model
     *
     * @param   Repository $model
     * @param   bool       $default
     * @param   callable   $handler
     * @param   string     $customName
     *
     * @return static Return self to support chaining.
     * @throws \ReflectionException
     */
    public function setRepository(Repository $model, $default = null, callable $handler = null, $customName = null)
    {
        // B/C
        if (is_string($handler)) {
            $customName = $handler;
        } elseif (is_callable($handler)) {
            $handler($model, $this);
        }

        $this->repository->setRepository($model, $default, $customName);

        return $this;
    }

    /**
     * Method to add model with name.
     *
     * @param string        $name
     * @param Repository    $model
     * @param callable|bool $handler
     * @param string        $default
     *
     * @return  static  Return self to support chaining.
     * @throws \ReflectionException
     */
    public function addRepository($name, Repository $model, callable $handler = null, $default = null)
    {
        // B/C
        if (is_bool($handler)) {
            $default = $handler;
        } elseif (is_callable($handler)) {
            $handler($model, $this);
        }

        $this->repository->setRepository($model, $default, $name);

        return $this;
    }

    /**
     * removeModel
     *
     * @param string $name
     *
     * @return  static
     */
    public function removeRepository($name)
    {
        $this->repository->removeModel($name);

        return $this;
    }

    /**
     * Method to get property Model
     *
     * @param  string $name
     *
     * @return Repository
     *
     * @deprecated use repository instead.
     */
    public function getModel($name = null)
    {
        return $this->getRepository($name);
    }

    /**
     * Method to set property model
     *
     * @param   Repository $model
     * @param   bool       $default
     * @param   string     $customName
     *
     * @return static Return self to support chaining.
     *
     * @throws \ReflectionException
     * @deprecated use repository instead.
     */
    public function setModel(Repository $model, $default = null, $customName = null)
    {
        $this->setRepository($model, $default, $customName);

        return $this;
    }

    /**
     * Method to add model with name.
     *
     * @param string     $name
     * @param Repository $model
     * @param string     $default
     *
     * @return  static  Return self to support chaining.
     *
     * @throws \ReflectionException
     * @deprecated use repository instead.
     */
    public function addModel($name, Repository $model, $default = null)
    {
        $this->addRepository($name, $model, $default);

        return $this;
    }

    /**
     * removeModel
     *
     * @param string $name
     *
     * @return  static
     *
     * @deprecated use repository instead.
     */
    public function removeModel($name)
    {
        $this->removeRepository($name);

        return $this;
    }

    /**
     * Pipe a callback to model and view then return value.
     *
     * @param string|callable $name    The name alias of model, keep NULL as default model.
     *                                 Or just send a callable here as handler.
     * @param callable        $handler The callback handler.
     *
     * @return  mixed
     */
    public function pipe($name, $handler = null)
    {
        if (is_callable($name)) {
            $handler = $name;
            $name    = null;
        }

        return $handler($this->getRepository($name), $this);
    }

    /**
     * Apply a callback to model and view data.
     *
     * @param string|callable $name    The name alias of model, keep NULL as default model.
     *                                 Or just send a callable here as handler.
     * @param callable        $handler The callback handler.
     *
     * @return  $this
     */
    public function applyData($name, $handler = null)
    {
        if (is_callable($name)) {
            $handler = $name;
            $name    = null;
        }

        $handler($this->getRepository($name), $this->getData());

        return $this;
    }

    /**
     * getRouter
     *
     * @return  PackageRouter
     */
    public function getRouter()
    {
        return $this->getPackage()->router;
    }

    /**
     * delegate
     *
     * @param   string $name
     * @param   array  $args
     *
     * @return   mixed
     */
    protected function delegate($name, ...$args)
    {
        if (!count($args)) {
            $args[] = $this->getData();
        }

        $name = str_replace('.', '_', $name);

        $name = StringNormalise::toCamelCase($name);

        if (is_callable([$this, $name])) {
            return $this->$name(...$args);
        }

        return null;
    }
}
