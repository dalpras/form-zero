<?php

declare(strict_types=1);

namespace DalPraS\FormZero\Element\Traits;

trait ChoicesAlignmentTrait
{
    /**
     * Opzioni in linea
     */
    private bool $inline = false;

    /**
     * Separator to use between options; defaults to '<br />'.
     */
    private string $separator = '<br />';

    public function isInline(): bool
    {
        return $this->inline;
    }

    public function setInline(bool $inline): static
    {
        $this->inline = $inline;
        return $this;
    }

    public function getSeparator(): string
    {
        return $this->separator;
    }

    public function setSeparator(string $separator): static
    {
        $this->separator = $separator;
        return $this;
    }
}
