<?php

declare(strict_types=1);

namespace DalPraS\UnitTests\Cleanup;

use DalPraS\FormZero\ZeroForm;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class LegacyStateCleanupTest extends TestCase
{
    public function testRenderedStateApiIsNotPartOfZeroFormAnymore(): void
    {
        $form = new ReflectionClass(ZeroForm::class);

        self::assertFalse($form->hasMethod('getIsRendered'));
        self::assertFalse($form->hasMethod('setIsRendered'));
        self::assertFalse($form->hasProperty('isRendered'));
    }
}
