<?php declare(strict_types=1);

namespace DalPraS\FormZero;

use DalPraS\FormZero\Factory\FormFactoryInterface;
use Laminas\Filter\FilterChain;
use Laminas\Validator\ValidatorChain;

interface ElementInterface
{
    public function addError(string $message): self;
    public function setAllowEmpty(bool $flag): self;
    public function getAllowEmpty(): bool;
    public function getAttribs(): array;
    public function getBelongsTo(): string;
    public function setBelongsTo(string $array): self;
    public function getFactory(): FormFactoryInterface;
    public function getFilterChain(): FilterChain;
    public function getFullyQualifiedName(): string;
    public function getId(): string;
    public function getIgnore(): bool;
    public function getLabel(): string;
    public function getName(): string;
    public function getValidatorChain(): ValidatorChain;
    public function getValue();
    public function setValue($value): self;
    public function hasErrors(): bool;
    public function isArray(): bool;
    public function isRequired(): bool;
    public function isValid($value, $context = null): bool;
    public function isTranslatorDisabled(): bool;
}
