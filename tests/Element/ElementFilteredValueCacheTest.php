<?php

declare(strict_types=1);

namespace DalPraS\UnitTests\Element;

use DalPraS\FormZero\Element;
use DalPraS\FormZero\Filter\FilterInterface;
use PHPUnit\Framework\TestCase;

final class ElementFilteredValueCacheTest extends TestCase
{
    public function testRepeatedGetValueFiltersOnlyOnce(): void
    {
        $filter = new CountingTrimFilter();
        $element = (new Element())->setValue('  value  ');
        $element->addFilters([$filter]);

        self::assertSame('value', $element->getValue());
        self::assertSame('value', $element->getValue());
        self::assertSame(1, $filter->calls);
    }

    public function testSetValueInvalidatesCachedFilteredValue(): void
    {
        $filter = new CountingTrimFilter();
        $element = new Element();
        $element->addFilters([$filter]);

        $element->setValue(' first ');
        self::assertSame('first', $element->getValue());

        $element->setValue(' second ');
        self::assertSame('second', $element->getValue());
        self::assertSame(2, $filter->calls);
    }

    public function testMutatingFilterChainInvalidatesCachedFilteredValue(): void
    {
        $first = new CountingTrimFilter();
        $second = new CountingSuffixFilter('-filtered');
        $element = (new Element())->setValue(' value ');
        $element->addFilters([$first]);

        self::assertSame('value', $element->getValue());

        // getFilterChain() is public API, so direct mutations must invalidate too.
        $element->getFilterChain()->attach($second);

        self::assertSame('value-filtered', $element->getValue());
        self::assertSame(2, $first->calls);
        self::assertSame(1, $second->calls);
    }

    public function testClearingFilterChainInvalidatesCachedFilteredValue(): void
    {
        $filter = new CountingTrimFilter();
        $element = (new Element())->setValue('  value  ');
        $element->addFilters([$filter]);

        self::assertSame('value', $element->getValue());

        $element->clearFilterChain();

        self::assertSame('  value  ', $element->getValue());
        self::assertSame(1, $filter->calls);
    }

    public function testArrayValuesAreFilteredRecursivelyOnlyOnce(): void
    {
        $filter = new CountingTrimFilter();
        $element = (new Element())
            ->setIsArray(true)
            ->setValue([' first ', [' second ', ' third ']]);
        $element->addFilters([$filter]);

        $expected = ['first', ['second', 'third']];
        self::assertSame($expected, $element->getValue());
        self::assertSame($expected, $element->getValue());
        self::assertSame(3, $filter->calls);
    }
}

final class CountingTrimFilter implements FilterInterface
{
    public int $calls = 0;

    public function filter(mixed $value): mixed
    {
        ++$this->calls;
        return is_string($value) ? trim($value) : $value;
    }
}

final class CountingSuffixFilter implements FilterInterface
{
    public int $calls = 0;

    public function __construct(private readonly string $suffix)
    {
    }

    public function filter(mixed $value): mixed
    {
        ++$this->calls;
        return is_string($value) ? $value . $this->suffix : $value;
    }
}
