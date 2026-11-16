<?php

declare(strict_types=1);

namespace DalPraS\UnitTests\Filter;

use DalPraS\FormZero\Filter\StringTrim;
use PHPUnit\Framework\TestCase;

final class StringTrimTest extends TestCase
{
    public function testItTrimsUnicodeWhitespace(): void
    {
        self::assertSame('Stefano', (new StringTrim())->filter("\u{202F}Stefano\u{202F}"));
    }
}
