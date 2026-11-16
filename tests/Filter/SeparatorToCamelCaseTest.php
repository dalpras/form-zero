<?php

declare(strict_types=1);

namespace DalPraS\UnitTests\Filter;

use DalPraS\FormZero\Filter\SeparatorToCamelCase;
use PHPUnit\Framework\TestCase;

final class SeparatorToCamelCaseTest extends TestCase
{
    public function testItSupportsAccentedCharacters(): void
    {
        self::assertSame('élodieDalPrà', (new SeparatorToCamelCase())->filter('ÉLODIE_dal-prà'));
        self::assertSame('ÉlodieDalPrà', (new SeparatorToCamelCase(['upperFirst' => true]))->filter('ÉLODIE_dal-prà'));
    }
}
