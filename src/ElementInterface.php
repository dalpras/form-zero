<?php declare(strict_types=1);

namespace DalPraS\FormZero;

use Laminas\Filter\FilterChain;
use DalPraS\FormZero\Factory\FormFactoryInterface;

interface ElementInterface
{
    public function getId(): string;
    public function getName(): string;
    public function setName(string $name): self;

    public function init(): void;
    
    public function setAllowEmpty(bool $flag): self;
    public function getAllowEmpty(): bool;

    public function getAttribs(): array;
    public function setAttrib(string $key, $value): void;
    public function addAttribs(array $attribs): void;
    public function setAttribs(array $attribs): void;
    public function getAttrib(string $key);
    public function removeAttrib(string $key): bool;

    public function getBelongsTo(): string;
    public function setBelongsTo(string $array): self;

    public function getFactory(): FormFactoryInterface;
    public function setFactory(FormFactoryInterface $factory): self;
    
    public function getFilterChain(): FilterChain;
    
    public function getFullyQualifiedName(): string;
    
    public function getIgnore(): bool;
    
    public function getLabel(): string;
    
    public function getValue();
    public function setValue($value): self;

    public function addError(string $message): self;
    public function hasErrors(): bool;
    
    public function isArray(): bool;
    
    public function isRequired(): bool;
    public function setRequired(bool $required = true): self;
    
    public function isValid($value, $context = null): bool;
    
    public function isTranslatorDisabled(): bool;
}
