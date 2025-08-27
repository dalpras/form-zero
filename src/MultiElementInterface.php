<?php declare(strict_types=1);

namespace DalPraS\FormZero;

interface MultiElementInterface extends ElementInterface
{
    public function addMultiOption(string $option, string $value = ''): self;

    public function addMultiOptions(array $options): self;

    public function setMultiOptions(array $options): self;

    public function getMultiOption(string $option): ?string;

    public function getMultiOptions(): array;

    public function removeMultiOption(string $option): bool;

    public function clearMultiOptions(): self;
}
