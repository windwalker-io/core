<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Pagination;

/**
 * The PaginationResult class.
 *
 * @since  2.0
 */
class PaginationResult
{
    /**
     * Property first.
     *
     * @var  integer
     */
    protected $first = null;

    /**
     * Property less.
     *
     * @var  integer
     */
    protected $less = null;

    /**
     * Property previous.
     *
     * @var  integer
     */
    protected $previous = null;

    /**
     * Property lowers.
     *
     * @var  array
     */
    protected $lowers = [];

    /**
     * Property current.
     *
     * @var  integer
     */
    protected $current = null;

    /**
     * Property highers.
     *
     * @var  array
     */
    protected $highers = [];

    /**
     * Property next.
     *
     * @var  integer
     */
    protected $next = null;

    /**
     * Property more.
     *
     * @var  integer
     */
    protected $more = null;

    /**
     * Property last.
     *
     * @var  integer
     */
    protected $last = null;

    /**
     * Method to get property First
     *
     * @return  int
     */
    public function getFirst()
    {
        return $this->first;
    }

    /**
     * Method to set property first
     *
     * @param   int $first
     *
     * @return  static  Return self to support chaining.
     */
    public function setFirst($first)
    {
        $this->first = $first;

        return $this;
    }

    /**
     * Method to get property Less
     *
     * @return  int
     */
    public function getLess()
    {
        return $this->less;
    }

    /**
     * Method to set property less
     *
     * @param   int $less
     *
     * @return  static  Return self to support chaining.
     */
    public function setLess($less)
    {
        $this->less = $less;

        return $this;
    }

    /**
     * Method to get property Previous
     *
     * @return  int
     */
    public function getPrevious()
    {
        return $this->previous;
    }

    /**
     * Method to set property previous
     *
     * @param   int $previous
     *
     * @return  static  Return self to support chaining.
     */
    public function setPrevious($previous)
    {
        $this->previous = $previous;

        return $this;
    }

    /**
     * Method to get property Lowers
     *
     * @return  array
     */
    public function getLowers()
    {
        return $this->lowers;
    }

    /**
     * Method to set property lowers
     *
     * @param   int $lowers
     *
     * @return  static  Return self to support chaining.
     */
    public function setLowers($lowers)
    {
        $this->lowers = $lowers;

        return $this;
    }

    /**
     * addLower
     *
     * @param integer $lower
     *
     * @return  $this
     */
    public function addLower($lower)
    {
        $this->lowers[] = $lower;

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
     * Method to get property Highers
     *
     * @return  array
     */
    public function getHighers()
    {
        return $this->highers;
    }

    /**
     * Method to set property highers
     *
     * @param   int $highers
     *
     * @return  static  Return self to support chaining.
     */
    public function setHighers($highers)
    {
        $this->highers = $highers;

        return $this;
    }

    /**
     * addHigher
     *
     * @param integer $higher
     *
     * @return  static
     */
    public function addHigher($higher)
    {
        $this->highers[] = $higher;

        return $this;
    }

    /**
     * Method to get property More
     *
     * @return  int
     */
    public function getMore()
    {
        return $this->more;
    }

    /**
     * Method to set property more
     *
     * @param   int $more
     *
     * @return  static  Return self to support chaining.
     */
    public function setMore($more)
    {
        $this->more = $more;

        return $this;
    }

    /**
     * Method to get property Last
     *
     * @return  int
     */
    public function getLast()
    {
        return $this->last;
    }

    /**
     * Method to set property last
     *
     * @param   int $last
     *
     * @return  static  Return self to support chaining.
     */
    public function setLast($last)
    {
        $this->last = $last;

        return $this;
    }

    /**
     * Method to get property Next
     *
     * @return  int
     */
    public function getNext()
    {
        return $this->next;
    }

    /**
     * Method to set property next
     *
     * @param   int $next
     *
     * @return  static  Return self to support chaining.
     */
    public function setNext($next)
    {
        $this->next = $next;

        return $this;
    }

    /**
     * getPages
     *
     * @return  array
     */
    public function getPages()
    {
        $pages = [];

        foreach ($this->getLowers() as $lower) {
            $pages[$lower] = Pagination::LOWER;
        }

        if ($this->getCurrent()) {
            $pages[$this->getCurrent()] = Pagination::CURRENT;
        }

        foreach ($this->getHighers() as $higher) {
            $pages[$higher] = Pagination::HIGHER;
        }

        ksort($pages);

        return $pages;
    }

    /**
     * getAll
     *
     * @return  array
     */
    public function getAll()
    {
        $all   = [];
        $pages = $this->getPages();

        if ($this->getFirst() !== null) {
            $all[$this->getFirst()] = Pagination::FIRST;
        }

        if ($this->getLess() !== null) {
            $all[$this->getLess()] = Pagination::LESS;
        }

        foreach ($pages as $k => $page) {
            $all[$k] = $page;
        }

        if ($this->getMore() !== null) {
            $all[$this->getMore()] = Pagination::MORE;
        }

        if ($this->getLast() !== null) {
            $all[$this->getLast()] = Pagination::LAST;
        }

        ksort($all);

        return $all;
    }
}
