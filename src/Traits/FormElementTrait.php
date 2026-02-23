<?php declare(strict_types=1);

namespace DalPraS\FormZero\Traits;

use InvalidArgumentException;

trait FormElementTrait 
{
    /**
     * Form name
     */
    private string $name = '';

    /**
     * is the translator disabled?
     */
    private bool $translatorDisabled = false;

    private function filterName(string $value, bool $allowBrackets = false): string
    {
        $pattern = $allowBrackets
            ? '/[^a-zA-Z0-9_\x7f-\xff\[\]]/'
            : '/[^a-zA-Z0-9_\x7f-\xff]/';
        return preg_replace($pattern, '', $value);
    }

    /**
     * Set form name
     */
    public function setName(string $name): static
    {
        $name = $this->filterName($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Invalid name provided; must contain only valid variable characters and be non-empty');
        }
        $this->name = $name;
        return $this;
    }

    /**
     * Get name attribute
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Indicate whether or not translation should be disabled
     */
    public function setDisableTranslator(bool $flag): static
    {
        $this->translatorDisabled = $flag;
        return $this;
    }

    /**
     * Get the translator disabled flag
     */
    public function isTranslatorDisabled(): bool
    {
        return $this->translatorDisabled;
    }

}

