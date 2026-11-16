<?php

declare(strict_types=1);

namespace DalPraS\UnitTests\Filter;

use DalPraS\FormZero\Filter\CamelCaseToDash;
use PHPUnit\Framework\TestCase;

final class CamelCaseToDashTest extends TestCase
{
    public function testItSeparatesAcronymsAndUnicodeWords(): void
    {
        $filter = new CamelCaseToDash();

        self::assertSame('xml-http-request', $filter->filter('XMLHttpRequest'));
        self::assertSame('città-di-roma', $filter->filter('CittàDiRoma'));
    }
}
