<?php

declare(strict_types=1);

namespace DalPraS\FormZero\Element\Traits;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

trait MultiChoicesTrait
{
    /**
     * Array of options for multi-item
     */    
    private array $choices = [];

    private array $choicesAttributes = [];

    abstract public function addConstraint(Constraint $constraint): void;
    abstract public function isRequired(): bool;

    protected function appendChoicesToConstraints(): void
    {
        $multiChoices = $this->getMultiChoices();
        $choices      = [];

        foreach ($multiChoices as $label => $value) {
            if (is_array($value)) {
                // grouped options: still [label => value] inside
                // we want the VALUES (what the browser submits)
                foreach ($value as $subLabel => $subValue) {
                    $choices[] = (string) $subValue;
                }
            } else {
                // single option: label => value
                $choices[] = (string) $value;
            }
        }

        if (!empty($choices)) {
            // When NOT required, '' must be a valid submitted value.
            if (!$this->isRequired() && !in_array('', $choices, true)) {
                $choices[] = '';
            }            
            $this->addConstraint(new Assert\Choice(['choices' => $choices]));
        }
    }


    /**
     * @return array<string,string> [label => value]
     */    
    public function getMultiChoices(): array { return $this->choices; }
    public function setMultiChoices(array $choices): self { $this->clearMultiChoices(); return $this->addMultiChoices($choices); }
    public function clearMultiChoices(): self { $this->choices = []; return $this; }
    public function addMultiChoices(array $choices): self {
        foreach ($choices as $label => $value) {
            if (is_array($value) 
                && array_key_exists('key', $value) 
                && array_key_exists('value', $value) 
            ) {
                $this->addMultiChoice((string) $value['key'], (string) $value['value']);
            } else {
                $this->addMultiChoice((string) $label, (string) $value);
            }
        }
        return $this;
    }

    public function getMultiChoice(string $label): ?string { return $this->choices[$label] ?? null; }
    public function addMultiChoice(string $label, string $value = ''): self { $this->choices[$label] = $value; return $this; }
    public function removeMultiChoice(string $label): bool { if (isset($this->choices[$label])) { unset($this->choices[$label]); return true; } return false; }

    public function getChoicesAttributes(): array { return $this->choicesAttributes; }
    public function setChoicesAttributes(array $choicesAttributes): self { $this->choicesAttributes = $choicesAttributes; return $this; }

    public function getChoiceAttributes(string $label): array { return (array) ($this->choicesAttributes[$label] ?? []); }
}
