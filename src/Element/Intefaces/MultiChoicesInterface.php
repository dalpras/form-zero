<?php declare(strict_types=1);

namespace DalPraS\FormZero\Element\Intefaces;

interface MultiChoicesInterface
{
    public function addMultiChoice(string $option, string $value = ''): self;
    public function addMultiChoices(array $options): self;
    public function setMultiChoices(array $options): self;
    public function getMultiChoice(string $option): ?string;
    public function getMultiChoices(): array;
    public function removeMultiChoice(string $option): bool;
    public function clearMultiChoices(): self;
}
