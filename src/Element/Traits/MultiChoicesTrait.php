<?php

declare(strict_types=1);

namespace DalPraS\FormZero\Element\Traits;

use DalPraS\FormZero\Element;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

trait MultiChoicesTrait
{
    /**
     * Array of options for multi-item
     */    
    private array $choices = [];

    /**
     * Which values are translated already?
     */    
    private array $translated = [];

    abstract public function addConstraint(Constraint $constraint): void;

    protected function appendChoicesToConstraints(): void
    {
        $multiChoices = $this->getMultiChoices();
        $choices      = [];

        foreach ($multiChoices as $optValue => $optLabel) {
            if (is_array($optLabel)) {
                $choices = array_merge($choices, array_keys($optLabel));
            } else {
                $choices[] = (string) $optValue;
            }
        }

        if (!empty($choices)) {
            $this->addConstraint(new Assert\Choice(['choices' => $choices]));
        }
    }

    public function addMultiChoice(string $option, string $value = ''): self
    {
        $this->choices[$option] = $value;
        return $this;
    }

    public function addMultiChoices(array $choices): self
    {
        foreach ($choices as $option => $value) {
            if (is_array($value) && array_key_exists('key', $value) && array_key_exists('value', $value) ) {
                $this->addMultiChoice((string) $value['key'], (string) $value['value']);
            } else {
                $this->addMultiChoice((string) $option, (string) $value);
            }
        }
        return $this;
    }

    public function setMultiChoices(array $choices): self
    {
        $this->clearMultiChoices();
        return $this->addMultiChoices($choices);
    }

    public function getMultiChoice(string $option): ?string
    {
        return $this->choices[$option] ?? null;
    }

    public function getMultiChoices(): array
    {
        return $this->choices;
    }

    public function removeMultiChoice(string $option): bool
    {
        if (isset($this->choices[$option])) {
            unset($this->choices[$option], $this->translated[$option]);
            return true;
        }
        return false;
    }

    public function clearMultiChoices(): self
    {
        $this->choices    = [];
        $this->translated = [];
        return $this;
    }
}
