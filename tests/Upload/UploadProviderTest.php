<?php

declare(strict_types=1);

namespace DalPraS\UnitTests\Upload;

use DalPraS\FormZero\Element\SymfileElement;
use DalPraS\FormZero\Factory\FormFactory;
use DalPraS\FormZero\FieldPath;
use DalPraS\FormZero\Upload\UploadedFileProviderInterface;
use DalPraS\SmartTemplate\TemplateEngine;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validation;

final class UploadProviderTest extends TestCase
{
    public function testUploadElementUsesItsCompiledNestedFieldPath(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'form-zero-upload-');
        self::assertNotFalse($tempFile);
        file_put_contents($tempFile, 'test');

        $uploadedFile = new UploadedFile($tempFile, 'test.txt', null, UPLOAD_ERR_OK, true);
        $provider = new RecordingUploadedFileProvider($uploadedFile);
        $factory = new FormFactory(
            template: new TemplateEngine(),
            uploadedFileProvider: $provider,
            validator: Validation::createValidator(),
        );

        /** @var SymfileElement $element */
        $element = $factory->createElement(SymfileElement::class, 'attachment', []);
        $element->setRenderBelongsTo('profile[documents]');

        self::assertSame($uploadedFile, $element->getUploadedFiles());
        self::assertSame('profile[documents][attachment]', $provider->lastPath?->toString());
    }
}

final class RecordingUploadedFileProvider implements UploadedFileProviderInterface
{
    public ?FieldPath $lastPath = null;

    public function __construct(private readonly UploadedFile $file)
    {
    }

    public function get(FieldPath $fieldPath): array|UploadedFile|null
    {
        $this->lastPath = $fieldPath;

        return $this->file;
    }
}
