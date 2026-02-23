<?php declare(strict_types=1);

namespace DalPraS\FormZero\Element\Intefaces;

interface MultiChoicesInterface
{
    public function getMultiChoices(): array;
    public function setMultiChoices(array $choices): static;
    public function addMultiChoices(array $choices): static;
    public function clearMultiChoices(): static;

    public function addMultiChoice(string $label, string $value = ''): static;
    public function getMultiChoice(string $label): ?string;
    public function removeMultiChoice(string $label): bool;

    public function getChoicesAttributes(): array;
    public function setChoicesAttributes(array $choicesAttributes): static;

    public function getChoiceAttributes(string $label): array;
}
