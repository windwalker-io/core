<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\View\Traits;

use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\View\Contract\FilterAwareViewModelInterface;
use Windwalker\Core\View\Traits\FilterAwareViewModelTrait;

#[CoversClass(FilterAwareViewModelTrait::class)]
class FilterAwareViewModelTraitTest extends TestCase
{
    private FilterAwareViewModelInterface $vm;

    #[Before]
    protected function prepareViewModel(): void
    {
        $this->vm = new class () implements FilterAwareViewModelInterface {
            use FilterAwareViewModelTrait;
        };
    }

    #[Test]
    public function isFilteredIsTrueWhenSearchTextIsProvided(): void
    {
        self::assertTrue($this->vm->isFiltered([], 'keyword'));
    }

    #[Test]
    public function isFilteredIsFalseWhenFilterAndSearchTextAreEmpty(): void
    {
        self::assertFalse($this->vm->isFiltered([]));
        self::assertFalse($this->vm->isFiltered(['state' => ''], ''));
    }

    #[Test]
    #[TestWith([['state' => '1']])]
    #[TestWith([['nested' => ['category' => '5']]])]
    #[TestWith([[0, '', 'x']])]
    public function isFilteredIsTrueWhenAnyFilterValueIsNonEmptyString(array $filter): void
    {
        self::assertTrue($this->vm->isFiltered($filter));
    }

    #[Test]
    public function isFilteredIsFalseWhenFilterValuesAreAllEmptyStrings(): void
    {
        self::assertFalse($this->vm->isFiltered(['state' => '', 'nested' => ['x' => '']]));
    }

    #[Test]
    public function isFilteredTreatsZeroAsNonEmptyFilterValue(): void
    {
        self::assertTrue($this->vm->isFiltered(['state' => 0]));
    }
}
