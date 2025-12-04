<?php

declare(strict_types=1);

namespace DalPraS\FormZero\Element\Intefaces;

interface ChoicesAlignmentInterface
{
    public function isInline(): bool;
    public function setInline(bool $inline): self;
    public function getSeparator(): string;
    public function setSeparator(string $separator): self;
}
