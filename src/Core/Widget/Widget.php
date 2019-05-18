<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Widget;

use Windwalker\Core\Ioc;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\NullPackage;
use Windwalker\Core\Package\PackageHelper;
use Windwalker\Core\Renderer\RendererHelper;
use Windwalker\Core\Utilities\Classes\ArrayAccessTrait;
use Windwalker\Data\Data;
use Windwalker\Data\DataInterface;
use Windwalker\Renderer\PhpRenderer;
use Windwalker\Renderer\RendererInterface;
use Windwalker\Utilities\Queue\PriorityQueue;

/**
 * The Widget class.
 *
 * @since  2.0
 */
class Widget implements \ArrayAccess
{
    use ArrayAccessTrait;

    /**
     * Property renderer.
     *
     * @var  RendererInterface
     */
    protected $renderer;

    /**
     * Property layout.
     *
     * @var string
     */
    protected $layout;

    /**
     * Property pathPrefix.
     *
     * @var  string
     */
    protected $pathPrefix;

    /**
     * Property pathRegistered.
     *
     * @var  bool
     */
    protected $pathRegistered = false;

    /**
     * Property debug.
     *
     * @var bool
     */
    protected $debug = false;

    /**
     * Property package.
     *
     * @var  AbstractPackage
     */
    protected $package;

    /**
     * Property shares.
     *
     * @var  array
     */
    protected $data = [];

    /**
     * Class init.
     *
     * @param string                   $layout
     * @param string|RendererInterface $renderer
     * @param string|AbstractPackage   $package
     */
    public function __construct($layout, $renderer = null, $package = null)
    {
        $this->layout = $layout;

        // Prepare renderer
        $this->renderer = $renderer ?: $this->renderer;
        $this->renderer = $this->renderer ?: RendererHelper::PHP;
        $this->renderer = $this->renderer instanceof RendererInterface ? $this->renderer : RendererHelper::getRenderer($this->renderer);

        if (!$package) {
            $package = Ioc::getConfig()->get('route.package');
        }

        if (is_string($package)) {
            $package = PackageHelper::getPackage($package);
        }

        if ($package) {
            $this->package = $package;
        }

        // Create PriorityQueue
        $this->createPriorityQueue();

        $this->init();
    }

    /**
     * initialise
     *
     * @return  void
     */
    protected function init()
    {
    }

    /**
     * render
     *
     * @param array $data
     *
     * @return string
     * @throws \ReflectionException
     */
    public function render($data = [])
    {
        $this->registerPaths();

        $data = new Data($data);

        $this->prepareData($data);

        $this->prepareGlobals($data);

        if ($this->isDebug()) {
            $data->paths = iterator_to_array(clone $this->getPaths());
        }

        return $this->renderer->render($this->layout, $data);
    }

    /**
     * prepareData
     *
     * @param  DataInterface $data
     *
     * @return  void
     */
    protected function prepareData(DataInterface $data)
    {
    }

    /**
     * Method to get property Layout
     *
     * @return  string
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * Method to set property layout
     *
     * @param   string $layout
     *
     * @return  static  Return self to support chaining.
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;

        return $this;
    }

    /**
     * Method to get property Renderer
     *
     * @return  RendererInterface
     */
    public function getRenderer()
    {
        return $this->renderer;
    }

    /**
     * Method to set property renderer
     *
     * @param   RendererInterface $renderer
     *
     * @return  static  Return self to support chaining.
     */
    public function setRenderer(RendererInterface $renderer)
    {
        $this->renderer = $renderer;

        return $this;
    }

    /**
     * prepareGlobals
     *
     * @param DataInterface $data
     *
     * @return  static
     */
    protected function prepareGlobals(DataInterface $data)
    {
        $data->layout   = $this->layout;
        $data->renderer = get_class($this->renderer);

        $data->package = $this->getPackage();
        $data->router  = $data->router ?: (clone $this->getPackage()->router)->mute(true);

        $global = new Data($this->getData());

        $data->bind($global->bind($data));

        return $this;
    }

    /**
     * registerPaths
     *
     * @param bool $refresh
     *
     * @return static
     * @throws \ReflectionException
     */
    public function registerPaths($refresh = false)
    {
        if (!$this->pathRegistered || $refresh) {
            // Set package path
            $package = $this->getPackage();

            if (!$package instanceof NullPackage) {
                $locale  = $package->app->get('language.locale', 'en-GB');
                $default = $package->app->get('language.default', 'en-GB');

                $prefix = $this->pathPrefix ? '/' . $this->pathPrefix : null;

                $this->renderer->addPath(
                    $package->getDir() . '/Templates' . $prefix . '/' . $locale,
                    PriorityQueue::BELOW_NORMAL
                );

                if ($locale !== $default) {
                    $this->renderer->addPath(
                        $package->getDir() . '/Templates' . $prefix . '/' . $default,
                        PriorityQueue::BELOW_NORMAL
                    );
                }

                $this->renderer->addPath($package->getDir() . '/Templates' . $prefix, PriorityQueue::BELOW_NORMAL);

                if ($this->pathPrefix) {
                    $this->renderer->addPath(
                        $package->app->get('path.templates') . '/' . $this->pathPrefix,
                        PriorityQueue::LOW
                    );
                }
            }

            $this->pathRegistered = true;
        }

        return $this;
    }

    /**
     * addPath
     *
     * @param string  $path
     * @param integer $priority
     *
     * @return  static
     */
    public function addPath($path, $priority = PriorityQueue::NORMAL)
    {
        $this->renderer->addPath($path, $priority);

        return $this;
    }

    /**
     * getPaths
     *
     * @return  PriorityQueue
     */
    public function getPaths()
    {
        return $this->renderer->getPaths();
    }

    /**
     * setPaths
     *
     * @param array|\SplPriorityQueue $paths
     *
     * @return  static
     */
    public function setPaths($paths)
    {
        $this->renderer->setPaths($paths);

        $this->createPriorityQueue();

        return $this;
    }

    /**
     * Method to get property Debug
     *
     * @return  boolean
     */
    public function isDebug()
    {
        return $this->debug;
    }

    /**
     * Method to set property debug
     *
     * @param   boolean $debug
     *
     * @return  static  Return self to support chaining.
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * createPriorityQueue
     *
     * @return  static
     */
    protected function createPriorityQueue()
    {
        $paths = $this->renderer->getPaths();

        if (!($paths instanceof PriorityQueue)) {
            $paths = new PriorityQueue($paths);

            $this->renderer->setPaths($paths);
        }

        return $this;
    }

    /**
     * Method to get property Package
     *
     * @return  AbstractPackage
     */
    public function getPackage()
    {
        if (!$this->package) {
            $this->package = new NullPackage();
        }

        return $this->package;
    }

    /**
     * Method to set property package
     *
     * @param   AbstractPackage|string $package
     *
     * @return  static  Return self to support chaining.
     */
    public function setPackage($package)
    {
        if ($package instanceof AbstractPackage) {
            $this->package = $package;
        } else {
            $this->package = PackageHelper::getPackage($package);
        }

        return $this;
    }

    /**
     * reset
     *
     * @return  static
     */
    public function reset()
    {
        $this->pathRegistered = false;

        if ($this->renderer instanceof PhpRenderer) {
            $this->renderer->reset();
        }

        $this->renderer->setPaths(new PriorityQueue());

        return $this;
    }

    /**
     * set
     *
     * @param   string $name
     * @param   mixed  $value
     *
     * @return  static
     */
    public function set($name, $value)
    {
        $this->data[$name] = $value;

        return $this;
    }

    /**
     * get
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return  mixed
     */
    public function get($name, $default = null)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        return $default;
    }

    /**
     * Method to get property Data
     *
     * @return  array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Method to set property data
     *
     * @param   array $data
     *
     * @return  static  Return self to support chaining.
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * __toString
     *
     * @return  string
     */
    public function __toString()
    {
        try {
            return $this->render();
        } catch (\Throwable $e) {
            echo $e;
        }

        return '';
    }

    /**
     * Method to get property PathPrefix
     *
     * @return  string
     */
    public function getPathPrefix()
    {
        return $this->pathPrefix;
    }

    /**
     * Method to set property pathPrefix
     *
     * @param   string $pathPrefix
     *
     * @return  static  Return self to support chaining.
     */
    public function setPathPrefix($pathPrefix)
    {
        $this->pathPrefix = $pathPrefix;

        return $this;
    }
}
