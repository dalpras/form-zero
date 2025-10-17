<?php declare(strict_types=1);

namespace DalPraS\FormZero\Element;

use DalPraS\FormZero\Element;
use DalPraS\FormZero\MultiElementInterface;
use Laminas\Validator\InArray;

class MultiElement extends Element implements MultiElementInterface
{
    /**
     * Array of options for multi-item
     */
    public array $options = [];

    /**
     * Opzioni in linea
     */
    private bool $inline = false;

    /**
     * Separator to use between options; defaults to '<br />'.
     */
    private string $separator = '<br />';

    /**
     * Which values are translated already?
     */
    private array $translated = [];

    public function isInline(): bool
    {
        return $this->inline;
    }

    public function setInline(bool $inline): self
    {
        $this->inline = $inline;
        return $this;
    }

    public function getSeparator(): string
    {
        return $this->separator;
    }

    public function setSeparator(string $separator): self
    {
        $this->separator = $separator;
        return $this;
    }

    /**
     * Add an option
     */
    public function addMultiOption(string $option, string $value = ''): self
    {
        $this->options[$option] = $value;
        return $this;
    }

    /**
     * Add many options at once
     */
    public function addMultiOptions(array $options): self
    {
        foreach ($options as $option => $value) {
            if (is_array($value) && array_key_exists('key', $value) && array_key_exists('value', $value) ) {
                $this->addMultiOption((string) $value['key'], (string) $value['value']);
            } else {
                $this->addMultiOption((string) $option, (string) $value);
            }
        }
        return $this;
    }

    /**
     * Set all options at once (overwrites)
     */
    public function setMultiOptions(array $options): self
    {
        $this->clearMultiOptions();
        return $this->addMultiOptions($options);
    }

    /**
     * Retrieve single multi option
     */
    public function getMultiOption(string $option): ?string
    {
        if (isset($this->options[$option])) {
            return $this->options[$option];
        }
        return null;
    }

    /**
     * Retrieve options
     */
    public function getMultiOptions(): array
    {
        return $this->options;
    }

    /**
     * Remove a single multi option
     */
    public function removeMultiOption(string $option): bool
    {
        if (isset($this->options[$option])) {
            unset($this->options[$option]);
            if (isset($this->translated[$option])) {
                unset($this->translated[$option]);
            }
            return true;
        }
        return false;
    }

    /**
     * Clear all options
     */
    public function clearMultiOptions(): self
    {
        $this->options = [];
        $this->translated = [];
        return $this;
    }

    /**
     * Is the value provided valid?
     *
     * Autoregisters InArray validator if necessary.
     *
     * @param string $value
     * @param mixed $context
     * @return bool
     */
    public function isValid($value, $context = null): bool
    {
        if ( empty($this->getValidator(InArray::class)) ) {
            $multiOptions = $this->getMultiOptions();
            $options      = [];

            foreach ($multiOptions as $opt_value => $opt_label) {
                // optgroup instead of option label
                if (is_array($opt_label)) {
                    $options = array_merge($options, array_keys($opt_label));
                } else {
                    $options[] = $opt_value;
                }
            }

            $this->getValidatorChain()->attachByName(InArray::class, ['haystack' => $options], true);
        }
        return parent::isValid($value, $context);
    }
}
