<?php

declare(strict_types=1);

namespace DalPraS\UnitTests\Filter;

use DalPraS\FormZero\Filter\ToBoolean;
use PHPUnit\Framework\TestCase;

final class ToBooleanTest extends TestCase
{
    public function testItRecognizesCapitalizedItalianYes(): void
    {
        self::assertTrue((new ToBoolean())->filter('SÌ'));
    }
}
