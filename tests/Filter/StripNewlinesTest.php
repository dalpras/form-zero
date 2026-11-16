<?php

declare(strict_types=1);

namespace DalPraS\UnitTests\Filter;

use DalPraS\FormZero\Filter\StripNewlines;
use PHPUnit\Framework\TestCase;

final class StripNewlinesTest extends TestCase
{
    public function testItStripsUnicodeLineSeparators(): void
    {
        self::assertSame('primasecondaterza', (new StripNewlines())->filter("prima\u{2028}seconda\u{2029}terza"));
    }
}
