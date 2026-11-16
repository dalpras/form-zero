<?php

declare(strict_types=1);

namespace DalPraS\UnitTests\Filter;

use DalPraS\FormZero\Filter\StringToLower;
use DalPraS\FormZero\Filter\StringToUpper;
use PHPUnit\Framework\TestCase;

final class StringCaseTest extends TestCase
{
    public function testItConvertsAccentedCharacters(): void
    {
        $encoding = mb_internal_encoding();
        mb_internal_encoding('ISO-8859-1');

        try {
            self::assertSame('perché', (new StringToLower())->filter('PERCHÉ'));
            self::assertSame('PERCHÉ', (new StringToUpper())->filter('perché'));
        } finally {
            mb_internal_encoding($encoding);
        }
    }
}
