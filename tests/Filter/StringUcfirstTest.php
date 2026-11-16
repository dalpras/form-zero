<?php

declare(strict_types=1);

namespace DalPraS\UnitTests\Filter;

use DalPraS\FormZero\Filter\StringUcfirst;
use PHPUnit\Framework\TestCase;

final class StringUcfirstTest extends TestCase
{
    public function testItUppercasesOnlyTheFirstCharacter(): void
    {
        $filter = new StringUcfirst();

        self::assertSame('Stefano dal prà', $filter->filter('stefano dal prà'));
        self::assertSame('Èlodie', $filter->filter('élodie'));
    }

    public function testItLeavesNonStringsAndEmptyStringsUntouched(): void
    {
        $filter = new StringUcfirst();

        self::assertSame('', $filter->filter(''));
        self::assertSame(123, $filter->filter(123));
        self::assertNull($filter->filter(null));
    }
}
