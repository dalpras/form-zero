<?php

declare(strict_types=1);

namespace DalPraS\UnitTests\Filter;

use DalPraS\FormZero\Filter\CamelCaseToSeparator;
use PHPUnit\Framework\TestCase;

final class CamelCaseToSeparatorTest extends TestCase
{
    public function testItAddsDefaultSeparatorBetweenCamelCaseWords(): void
    {
        self::assertSame('Camel-Case-To-Separator', (new CamelCaseToSeparator())->filter('CamelCaseToSeparator'));
        self::assertSame('XML-Http-Request', (new CamelCaseToSeparator())->filter('XMLHttpRequest'));
        self::assertSame('Suite-API-License2-Dto', (new CamelCaseToSeparator())->filter('SuiteAPILicense2Dto'));
    }

    public function testItSupportsCustomSeparator(): void
    {
        self::assertSame('Camel_Case', (new CamelCaseToSeparator('_'))->filter('CamelCase'));
        self::assertSame('Camel Case', (new CamelCaseToSeparator(['separator' => ' ']))->filter('CamelCase'));
    }

    public function testItCanLowercaseTheResult(): void
    {
        self::assertSame('camel-case', (new CamelCaseToSeparator(['lowercase' => true]))->filter('CamelCase'));
    }

    public function testItLeavesNonStringsUntouched(): void
    {
        $filter = new CamelCaseToSeparator();

        self::assertSame(123, $filter->filter(123));
        self::assertSame(null, $filter->filter(null));
        self::assertSame('', $filter->filter(''));
    }
}
