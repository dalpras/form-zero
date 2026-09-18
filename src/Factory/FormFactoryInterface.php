<?php declare(strict_types=1);

namespace DalPraS\FormZero\Factory;

use DalPraS\FormZero\ElementInterface;
use DalPraS\FormZero\ZeroForm;
use DalPraS\FormZero\Upload\UploadedFileProviderInterface;
use DalPraS\SmartTemplate\TemplateEngine;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

interface FormFactoryInterface
{
    public function template(): TemplateEngine;

    public function getUploadedFileProvider(): UploadedFileProviderInterface;

    public function translator(): ?Translator;

    public function createElement(ElementInterface|string $element, string $name, array $options): ElementInterface;

    public function createForm(string $class, mixed ...$args): ZeroForm;

    public function getIgnoreCsrfToken(): bool;

    public function getValidator(): ValidatorInterface;

    public function setIgnoreCsrfToken(bool $disableCsrfToken = true): FormFactoryInterface;
}
