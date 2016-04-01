<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Pagination;

use Windwalker\Core\Ioc;
use Windwalker\Core\Renderer\RendererHelper;
use Windwalker\Core\Router\RestfulRouter;
use Windwalker\Core\Router\Router;
use Windwalker\Renderer\PhpRenderer;
use Windwalker\Uri\Uri;

/**
 * The Pagination class.
 *
 * @note   Based on https://github.com/Kilte/pagination
 *
 * @since  2.0
 */
class Pagination
{
	/**
	 * Number of the first page
	 */
	const BASE_PAGE = 1;

	/**
	 * First page
	 */
	const FIRST = 'first';

	/**
	 * The page before the previous neighbour pages
	 */
	const LESS = 'less';

	/**
	 * Previous pages
	 */
	const PREVIOUS = 'previous';

	/**
	 * Previous pages
	 */
	const LOWER = 'lower';

	/**
	 * Current page
	 */
	const CURRENT = 'current';

	/**
	 * Next pages
	 */
	const HIGHER = 'higher';

	/**
	 * Next pages
	 */
	const NEXT = 'next';

	/**
	 * The page after the next neighbour pages
	 */
	const MORE = 'more';

	/**
	 * Last page
	 */
	const LAST = 'last';

	/**
	 * Total Items
	 *
	 * @var int
	 */
	protected $total;

	/**
	 * Number of the current page
	 *
	 * @var int
	 */
	protected $current;

	/**
	 * Items per page
	 *
	 * @var int
	 */
	protected $limit;

	/**
	 * Total pages
	 *
	 * @var int
	 */
	protected $pages;

	/**
	 * Offset
	 *
	 * @var int
	 */
	protected $offset;

	/**
	 * Number of neighboring pages at the left and the right sides
	 *
	 * @var int
	 */
	protected $neighbours;

	/**
	 * Property result.
	 *
	 * @var  PaginationResult
	 */
	protected $result = null;

	/**
	 * Create instance
	 *
	 * @param int $total       Total items
	 * @param int $current     Number of the current page
	 * @param int $limit       Items per page
	 * @param int $neighbours  Number of neighboring pages at the left and the right sides
	 *
	 * @throws \LogicException
	 */
	public function __construct($total, $current, $limit = 10, $neighbours = 4)
	{
		$this->total   = (int) $total;
		$this->current = (int) $current;
		$this->limit   = (int) $limit;
		$this->neighbours = (int) $neighbours;

		if ($this->limit < 0)
		{
			throw new \InvalidArgumentException('Items per page must be at least 1');
		}

		if ($this->neighbours <= 0)
		{
			throw new \InvalidArgumentException('Number of neighboring pages must be at least 1');
		}

		if ($this->current < self::BASE_PAGE)
		{
			$this->current = self::BASE_PAGE;
		}

		if ($this->limit == 0)
		{
			$this->pages = 1;
		}
		else
		{
			$this->pages = (int) ceil($this->total / $this->limit);
		}

		if ($this->current > $this->pages)
		{
			$this->current = $this->pages;
		}

		$this->offset = abs(intval($this->current * $this->limit - $this->limit));
	}

	/**
	 * Display
	 *
	 * @return static
	 */
	public function build()
	{
		$this->result = new PaginationResult;

		if ($this->total == 0 || $this->limit == 0 || $this->pages == 1)
		{
			return array();
		}

		// Lower
		$offset = $this->current - 1;
		$neighbours  = $this->current - $this->neighbours;
		$neighbours  = $neighbours < static::BASE_PAGE ? static::BASE_PAGE : $neighbours;

		for ($i = $offset; $i >= $neighbours; $i--)
		{
			$this->result->addLower($i);
		}

		if ($neighbours - static::BASE_PAGE >= 2)
		{
			$this->result->setLess($neighbours - 1);
			$this->result->setPrevious($this->current - 1);
		}

		// First
		if ($this->current - $this->neighbours > static::BASE_PAGE)
		{
			$this->result->setFirst(static::BASE_PAGE);
		}

		// Higher
		$offset = $this->current + 1;
		$neighbours  = $this->current + $this->neighbours;
		$neighbours  = $neighbours > $this->pages ? $this->pages : $neighbours;

		for ($i = $offset; $i <= $neighbours; $i++)
		{
			$this->result->addHigher($i);
		}

		if ($this->pages - $neighbours > 0)
		{
			$this->result->setMore($neighbours + 1);
			$this->result->setNext($this->current + 1);
		}

		// Last
		if ($this->current + $this->neighbours < $this->pages)
		{
			$this->result->setLast($this->pages);
		}

		// Current
		$this->result->setCurrent($this->current);

		return $this;
	}

	/**
	 * Method to get property Total
	 *
	 * @return  int
	 */
	public function getTotal()
	{
		return $this->total;
	}

	/**
	 * Method to set property total
	 *
	 * @param   int $total
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setTotal($total)
	{
		$this->total = $total;

		return $this;
	}

	/**
	 * Method to get property Current
	 *
	 * @return  int
	 */
	public function getCurrent()
	{
		return $this->current;
	}

	/**
	 * Method to set property current
	 *
	 * @param   int $current
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setCurrent($current)
	{
		$this->current = $current;

		return $this;
	}

	/**
	 * Method to get property Offset
	 *
	 * @return  int
	 */
	public function getOffset()
	{
		return $this->offset;
	}

	/**
	 * Method to set property offset
	 *
	 * @param   int $offset
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setOffset($offset)
	{
		$this->offset = $offset;

		return $this;
	}

	/**
	 * Method to get property Result
	 *
	 * @return  PaginationResult
	 */
	public function getResult()
	{
		$this->build();

		return $this->result;
	}

	/**
	 * Method to set property result
	 *
	 * @param   PaginationResult $result
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setResult($result)
	{
		$this->result = $result;

		return $this;
	}

	/**
	 * render
	 *
	 * @param string|\Closure $route
	 * @param array           $query
	 * @param string          $template
	 *
	 * @return string
	 */
	public function render($route = null, $query = array(), $template = 'windwalker.pagination.default')
	{
		// B/C
		if (is_string($query))
		{
			$template = $query;
			
			$query = array();
		}

		$renderer = new PhpRenderer(RendererHelper::getGlobalPaths());

		$result = $this->getResult();

		if (!$result->getPages())
		{
			return null;
		}

		if ($route === null)
		{
			$uri = Ioc::get('system.uri')->get('route');

			$route = function ($queries) use ($uri, $query)
			{
				$uri = new Uri($uri);

				$queries = array_merge($queries, $query);

				foreach ($queries as $k => $v)
				{
					$uri->setVar($k, $v);
				}

				return $uri->toString();
			};
		}

		if (!($route instanceof \Closure))
		{
			$route = function ($queries, $type = RestfulRouter::TYPE_PATH) use ($route, $query)
			{
				$queries = array_merge($queries, $query);

				return Router::html($route, $queries, $type);
			};
		}

		return $renderer->render($template, array('pagination' => $result, 'route' => $route));
	}
}
