<?php

/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

declare(strict_types=1);

namespace Windwalker\Core\Pagination;

use Exception;
use Traversable;

/**
 * The PaginationResult class.
 *
 * @since  2.0
 */
class PaginationResult implements \JsonSerializable, \IteratorAggregate
{
    /**
     * Property first.
     *
     * @var ?PageItem
     */
    protected ?PageItem $first = null;

    /**
     * Property less.
     *
     * @var ?PageItem
     */
    protected ?PageItem $less = null;

    /**
     * Property previous.
     *
     * @var ?PageItem
     */
    protected ?PageItem $previous = null;

    /**
     * Property lowers.
     *
     * @var  array<int, PageItem>
     */
    protected array $lowers = [];

    /**
     * Property current.
     *
     * @var ?PageItem
     */
    protected ?PageItem $current = null;

    /**
     * Property highers.
     *
     * @var  array<int, PageItem>
     */
    protected array $highers = [];

    /**
     * Property next.
     *
     * @var ?PageItem
     */
    protected ?PageItem $next = null;

    /**
     * Property more.
     *
     * @var ?PageItem
     */
    protected ?PageItem $more = null;

    /**
     * Property last.
     *
     * @var ?PageItem
     */
    protected ?PageItem $last = null;

    /**
     * PaginationResult constructor.
     *
     * @param  Pagination  $pagination
     */
    public function __construct(protected Pagination $pagination)
    {
        $this->compile($pagination);
    }

    protected function compile(Pagination $pagination): void
    {
        $total = $pagination->getTotal();
        $current = $pagination->getCurrent();

        if ($total === 0 || $pagination->getLimit() === 0 || $this->getPages() === 1) {
            return;
        }

        // Lower
        $offset = $current - 1;
        $pages  = $pagination->getPages();

        // -1 is simple pagination
        if ($total === -1) {
            $neighbours = 1;
        } else {
            $neighbours = $current - $pagination->getNeighbours();
            $neighbours = $neighbours < Pagination::BASE_PAGE ? Pagination::BASE_PAGE : $neighbours;
        }

        for ($i = $offset; $i >= $neighbours; $i--) {
            $this->lowers[] = $this->getItem(Pagination::LOWER, $i);
        }

        if ($neighbours - Pagination::BASE_PAGE >= 2) {
            $this->less = $this->getItem(Pagination::LESS, $neighbours - 1);
        }

        $this->previous = $this->getItem(Pagination::PREVIOUS, $current - 1);

        // First
        if ($current - $pagination->getNeighbours() > Pagination::BASE_PAGE) {
            $this->first = $this->getItem(Pagination::FIRST, Pagination::BASE_PAGE);
        }

        // Higher
        $offset = $current + 1;
        $neighbours = $current + $pagination->getNeighbours();
        $neighbours = $neighbours > $pages ? $pages : $neighbours;

        for ($i = $offset; $i <= $neighbours; $i++) {
            $this->highers[] = $this->getItem(Pagination::HIGHER, $i);
        }

        if (($pages - $neighbours) > 0) {
            $this->more = $this->getItem(Pagination::MORE, $neighbours + 1);
        }

        // Show next button if not last page or for simple pagination
        if ($total === -1 || $current !== $pages) {
            $this->next = $this->getItem(Pagination::NEXT, $current + 1);
        }

        // Last
        if ($current + $pagination->getNeighbours() < $pages) {
            $this->last = $this->getItem(Pagination::LAST, $pages);
        }

        // Current
        $this->current = $this->getItem(Pagination::CURRENT, $current);
    }

    /**
     * @return PageItem|null
     */
    public function getFirst(): ?PageItem
    {
        return $this->first;
    }

    /**
     * @return PageItem|null
     */
    public function getLess(): ?PageItem
    {
        return $this->less;
    }

    /**
     * @return PageItem|null
     */
    public function getPrevious(): ?PageItem
    {
        return $this->previous;
    }

    /**
     * @return PageItem[]
     */
    public function getLowers(): array
    {
        return $this->lowers;
    }

    /**
     * @return PageItem|null
     */
    public function getCurrent(): ?PageItem
    {
        return $this->current;
    }

    /**
     * @return PageItem[]
     */
    public function getHighers(): array
    {
        return $this->highers;
    }

    /**
     * @return PageItem|null
     */
    public function getNext(): ?PageItem
    {
        return $this->next;
    }

    /**
     * @return PageItem|null
     */
    public function getMore(): ?PageItem
    {
        return $this->more;
    }

    /**
     * @return PageItem|null
     */
    public function getLast(): ?PageItem
    {
        return $this->last;
    }

    protected function getItem(string $name, ?int $page): ?PageItem
    {
        if ($page === null) {
            return null;
        }

        return new PageItem(
            $name,
            $page,
            $this->pagination->toRoute($page)
        );
    }

    /**
     * getPages
     *
     * @return  array
     */
    public function getPages(): array
    {
        $pages = [];

        foreach ($this->getLowers() as $lower) {
            $pages[$lower->page] = $lower;
        }

        if ($current = $this->getCurrent()) {
            $pages[$current->page] = $current;
        }

        foreach ($this->getHighers() as $higher) {
            $pages[$higher->page] = $higher;
        }

        ksort($pages);

        return $pages;
    }

    /**
     * getAll
     *
     * @return  array<int, PageItem>
     */
    public function getAll(): array
    {
        $all   = [];
        $pages = $this->getPages();

        if ($first = $this->getFirst()) {
            $all[$first->page] = $first;
        }

        if ($less = $this->getLess()) {
            $all[$less->page] = $less;
        }

        foreach ($pages as $k => $page) {
            $all[$k] = $page;
        }

        if ($more = $this->getMore()) {
            $all[$more->page] = $more;
        }

        if ($last = $this->getLast()) {
            $all[$last->page] = $last;
        }

        ksort($all);

        return $all;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link  https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4
     */
    public function jsonSerialize(): mixed
    {
        return $this->getAll();
    }

    /**
     * Retrieve an external iterator
     * @link https://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @throws Exception on failure.
     */
    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->getAll());
    }
}
