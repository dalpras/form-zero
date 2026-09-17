<?php

declare(strict_types=1);

namespace DalPraS\UnitTests\Factory;

use ArgumentCountError;
use DalPraS\FormZero\Factory\FormFactory;
use DalPraS\SmartTemplate\TemplateEngine;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validation;

final class FormFactoryTest extends TestCase
{
    public function testItRequiresAValidatorDependency(): void
    {
        $this->expectException(ArgumentCountError::class);

        new FormFactory(
            new TemplateEngine(),
            new Request(),
        );
    }

    public function testItUsesTheInjectedValidatorInstance(): void
    {
        $validator = Validation::createValidator();
        $factory = new FormFactory(
            template: new TemplateEngine(),
            request: new Request(),
            validator: $validator,
        );

        self::assertSame($validator, $factory->getValidator());
    }
}
