<?php declare(strict_types=1);

namespace DalPraS\FormZero;

use Laminas\Filter\FilterChain;
use DalPraS\FormZero\Factory\FormFactoryInterface;

interface ElementInterface
{
    // Identity / naming
    public function getId(): string;
    public function getName(): string;
    public function setName(string $name): static;
    public function getBelongsTo(): string;
    public function setBelongsTo(string $array): static;
    public function getFullyQualifiedName(): string;
    public function isArray(): bool;

    // Lifecycle / initialization
    public function init(): void;
    public function initOptions(array $options): static;

    // Value
    public function getValue();
    public function setValue($value): static;

    // Validation / requirements / errors
    public function isValid($value, $context = null): bool;
    public function isRequired(): bool;
    public function setRequired(bool $required = true): static;
    public function getAllowEmpty(): bool;
    public function setAllowEmpty(bool $flag): static;
    public function hasErrors(): bool;
    public function addError(string $message): static;

    // Attributes
    public function getAttrib(string $key);
    public function getAttribs(): array;
    public function setAttrib(string $key, $value): void;
    public function setAttribs(array $attribs): void;
    public function addAttribs(array $attribs): void;
    public function removeAttrib(string $key): bool;

    // Presentation / behavior flags
    public function getLabel(): string;
    public function getIgnore(): bool;
    public function isTranslatorDisabled(): bool;

    // Filtering
    public function getFilterChain(): FilterChain;

    // Dependencies
    public function getFactory(): FormFactoryInterface;
    public function setFactory(FormFactoryInterface $factory): static;
}