<?php declare(strict_types=1);

namespace DalPraS\FormZero\Element\Intefaces;

interface MultiChoicesInterface
{
    public function getMultiChoices(): array;
    public function setMultiChoices(array $choices): self;
    public function addMultiChoices(array $choices): self;
    public function clearMultiChoices(): self;

    public function addMultiChoice(string $label, string $value = ''): self;
    public function getMultiChoice(string $label): ?string;
    public function removeMultiChoice(string $label): bool;

    public function getChoicesAttributes(): array;
    public function setChoicesAttributes(array $choicesAttributes): self;

    public function getChoiceAttributes(string $label): array;
}
