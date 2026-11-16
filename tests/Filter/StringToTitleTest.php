<?php

declare(strict_types=1);

namespace DalPraS\UnitTests\Filter;

use DalPraS\FormZero\Filter\StringToTitle;
use PHPUnit\Framework\TestCase;

final class StringToTitleTest extends TestCase
{
    public function testItTitleCasesEveryPartOfAFullName(): void
    {
        $filter = new StringToTitle();

        self::assertSame('Stefano Dal Prà', $filter->filter('STEFANO DAL PRÀ'));
        self::assertSame("D'Angelo Marie-Claire", $filter->filter("d'ANGELO MARIE-CLAIRE"));
    }

    public function testItLeavesNonStringsAndEmptyStringsUntouched(): void
    {
        $filter = new StringToTitle();

        self::assertSame('', $filter->filter(''));
        self::assertSame(123, $filter->filter(123));
        self::assertNull($filter->filter(null));
    }
}
