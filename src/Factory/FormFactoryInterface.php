<?php declare(strict_types=1);

namespace DalPraS\FormZero\Factory;

use DalPraS\FormZero\ElementInterface;
use DalPraS\FormZero\ZeroForm;
use DalPraS\SmartTemplate\TemplateEngine;
use Psr\Http\Message\ServerRequestInterface;

interface FormFactoryInterface
{
    public function createForm(string $class, ...$args): ZeroForm;
    
    public function createElement(ElementInterface|string $element, string $name, array $options): ElementInterface;

    public function getTemplate(): TemplateEngine;

    public function getTemplateFile(): string;
    
    public function setIgnoreCsrfToken(bool $disableCsrfToken = true): FormFactoryInterface;

    public function getIgnoreCsrfToken(): bool;

    public function getPsrRequest(): ServerRequestInterface;

}
