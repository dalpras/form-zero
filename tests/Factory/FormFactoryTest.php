<?php

declare(strict_types=1);

namespace DalPraS\UnitTests\Factory;

use ArgumentCountError;
use DalPraS\FormZero\Factory\FormFactory;
use DalPraS\FormZero\FieldPath;
use DalPraS\FormZero\Upload\UploadedFileProviderInterface;
use DalPraS\SmartTemplate\TemplateEngine;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validation;

final class FormFactoryTest extends TestCase
{
    public function testItRequiresAValidatorDependency(): void
    {
        $this->expectException(ArgumentCountError::class);

        new FormFactory(
            new TemplateEngine(),
            $this->uploadedFileProvider(),
        );
    }

    public function testItUsesTheInjectedValidatorInstance(): void
    {
        $validator = Validation::createValidator();
        $factory = new FormFactory(
            template: new TemplateEngine(),
            uploadedFileProvider: $this->uploadedFileProvider(),
            validator: $validator,
        );

        self::assertSame($validator, $factory->getValidator());
    }

    public function testItUsesTheInjectedUploadedFileProviderInstance(): void
    {
        $uploadedFileProvider = $this->uploadedFileProvider();
        $factory = new FormFactory(
            template: new TemplateEngine(),
            uploadedFileProvider: $uploadedFileProvider,
            validator: Validation::createValidator(),
        );

        self::assertSame($uploadedFileProvider, $factory->getUploadedFileProvider());
    }

    private function uploadedFileProvider(): UploadedFileProviderInterface
    {
        return new class implements UploadedFileProviderInterface {
            public function get(FieldPath $fieldPath): array|UploadedFile|null
            {
                return null;
            }
        };
    }
}
