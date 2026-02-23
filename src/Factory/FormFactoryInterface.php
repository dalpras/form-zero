<?php declare(strict_types=1);

namespace DalPraS\FormZero\Factory;

use DalPraS\FormZero\ElementInterface;
use DalPraS\FormZero\ZeroForm;
use DalPraS\SmartTemplate\TemplateEngine;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

interface FormFactoryInterface
{
    public function createElement(ElementInterface|string $element, string $name, array $options): ElementInterface;
    public function createForm(string $class, ...$args): ZeroForm;
    public function getHttpRequest(): Request;
    public function getIgnoreCsrfToken(): bool;
    public function getTemplate(): TemplateEngine;
    public function getTemplateFile(): string;
    public function getTranslator(): ?Translator;
    public function getValidator(): ValidatorInterface;
    public function setIgnoreCsrfToken(bool $disableCsrfToken = true): FormFactoryInterface;
}
