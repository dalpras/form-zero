<?php declare(strict_types=1);

namespace DalPraS\FormZero\Element\Intefaces;

interface MultiChoicesInterface
{
    public function addMultiChoice(string $label, string $value = ''): self;
    public function addMultiChoices(array $choices): self;
    public function setMultiChoices(array $choices): self;
    public function getMultiChoice(string $label): ?string;
    public function getMultiChoices(): array;
    public function removeMultiChoice(string $label): bool;
    public function clearMultiChoices(): self;
}
