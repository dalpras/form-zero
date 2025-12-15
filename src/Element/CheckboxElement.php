<?php declare(strict_types=1);

namespace DalPraS\FormZero\Element;

use DalPraS\FormZero\Element;
use DalPraS\FormZero\Element\Intefaces\ChoicesAlignmentInterface;
use DalPraS\FormZero\Element\Traits\ChoicesAlignmentTrait;

final class CheckboxElement extends Element implements ChoicesAlignmentInterface
{
    use ChoicesAlignmentTrait;
    
    /**
     * Options that will be passed to the view helper
     */
    // public array $options = [
    //     'checkedValue'   => "true",
    //     'uncheckedValue' => "false",
    // ];

    /**
     * Is the checkbox checked?
     */
    public bool $checked = false;

    /**
     * Value when checked
     */
    private string $checkedValue = "true";

    /**
     * Value when not checked
     */
    private string $uncheckedValue = "false";

    /**
     * Set options
     *
     * Intercept checked and unchecked values and set them early; test stored
     * value against checked and unchecked values after configuration.
     */
    public function initOptions(array $options): self
    {
        if (array_key_exists('checkedValue', $options)) {
            $this->setCheckedValue($options['checkedValue']);
            unset($options['checkedValue']);
        }
        if (array_key_exists('uncheckedValue', $options)) {
            $this->setUncheckedValue($options['uncheckedValue']);
            unset($options['uncheckedValue']);
        }
        parent::initOptions($options);

        $curValue = $this->getValue();
        $test     = array($this->getCheckedValue(), $this->getUncheckedValue());
        if (!in_array($curValue, $test)) {
            $this->setValue($curValue);
        }

        return $this;
    }

    /**
     * Set value
     *
     * If value matches checked value, sets to that value, and sets the checked
     * flag to true.
     *
     * Any other value causes the unchecked value to be set as the current
     * value, and the checked flag to be set as false.
     *
     * @param mixed $value
     */
    public function setValue($value): self
    {
        if ($value == $this->getCheckedValue()) {
            parent::setValue($value);
            $this->checked = true;
        } else {
            parent::setValue($this->getUncheckedValue());
            $this->checked = false;
        }
        return $this;
    }

    /**
     * Set checked value
     */
    public function setCheckedValue(string $value): self
    {
        $this->checkedValue = $value;
        // $this->options['checkedValue'] = $value;
        return $this;
    }

    /**
     * Get value when checked
     */
    public function getCheckedValue(): string
    {
        return $this->checkedValue;
    }

    /**
     * Set unchecked value
     */
    public function setUncheckedValue(string $value): self
    {
        $this->uncheckedValue = $value;
        // $this->options['uncheckedValue'] = $value;
        return $this;
    }

    /**
     * Get value when not checked
     */
    public function getUncheckedValue(): string
    {
        return $this->uncheckedValue;
    }

    /**
     * Set checked flag
     */
    public function setChecked(bool $flag): self
    {
        $this->checked = $flag;
        if ($this->checked) {
            $this->setValue($this->getCheckedValue());
        } else {
            $this->setValue($this->getUncheckedValue());
        }
        return $this;
    }

    /**
     * Get checked flag
     */
    public function isChecked(): bool
    {
        return $this->checked;
    }
}
