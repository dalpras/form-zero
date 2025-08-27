<?php declare(strict_types=1);

namespace DalPraS\FormZero\Traits;

use DalPraS\FormZero\Decorator\AbstractDecorator;
use InvalidArgumentException;

trait FormElementTrait {

    /**
     * Form name
     */
    private string $name = '';

    /**
     * Custom error messages
     */
    private array $errorMessages = [];

    /**
     * Decorators for rendering
     *
     * @var \DalPraS\FormZero\Decorator\AbstractDecorator[]
     */
    private array $decorators = [];

    /**
     * is the translator disabled?
     */
    private bool $translatorDisabled = false;

    /**
     * Attributes
     */
    protected array $attribs = [];

    /**
     * Filter a name to only allow valid variable characters
     */
    // private function filterName(string $value, bool $allowBrackets = false): string
    // {
    //     $charset = '^a-zA-Z0-9_\x7f-\xff';
    //     if ($allowBrackets) {
    //         $charset .= '\[\]';
    //     }
    //     return preg_replace('/[' . $charset . ']/', '', $value);
    // }
    
    private function filterName(string $value, bool $allowBrackets = false): string
    {
        $pattern = $allowBrackets
            ? '/[^a-zA-Z0-9_\x7f-\xff\[\]]/'
            : '/[^a-zA-Z0-9_\x7f-\xff]/';
        return preg_replace($pattern, '', $value);
    }


    // Form metadata:

    /**
     * Set form name
     */
    public function setName(string $name): self
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
     * Retrieve custom error messages
     */
    public function getErrorMessages(): array
    {
        return $this->errorMessages;
    }

    /**
     * Clear custom error messages stack
     */
    public function clearErrorMessages(): void
    {
        $this->errorMessages = [];
    }

    /**
     * Add a custom error message to return in the event of failed validation
     */
    public function addErrorMessage(string $message): self
    {
        $this->errorMessages[] = (string) $message;
        return $this;
    }

    /**
     * Are there custom error messages registered?
     */
    public function hasErrorMessages(): bool
    {
        return !empty($this->errorMessages);
    }

    /**
     * Add an error message and mark element as failed validation
     */
    public function addError(string $message): self
    {
        $this->addErrorMessage($message);
        $this->markAsError();
        return $this;
    }

    /**
     * Add multiple error messages and flag element as failed validation
     */
    public function addErrors(array $messages): self
    {
        foreach ($messages as $message) {
            $this->addError($message);
        }
        return $this;
    }

    /**
     * Overwrite any previously set error messages and flag as failed validation
     */
    public function setErrors(array $messages): self
    {
        $this->clearErrorMessages();
        return $this->addErrors($messages);
    }

    /**
     * Add many decorators at once
     */
    public function addDecorators(array $decorators): self
    {
        foreach ($decorators as $decorator) {
            $this->addDecorator($decorator);
        }
        return $this;
    }

    /**
     * Overwrite all decorators
     */
    public function setDecorators(array $decorators): self
    {
        $this->clearDecorators();
        return $this->addDecorators($decorators);
    }

    /**
     * Retrieve all decorators or only some by class name.
     */
    public function getDecorators(?string $name = null): array
    {
        if ($name === null) {
            return $this->decorators;
        }

        $result = [];
        foreach ($this->decorators as $key => &$decorator) {
            if (get_class($decorator) === $name) {
                $result[$key] = $decorator;
            }
        }
        return $result;
    }

    /**
     * Clear all decorators
     */
    public function clearDecorators(): self
    {
        $this->decorators = [];
        return $this;
    }

    /**
     * Add a decorator for rendering the element
     */
    public function addDecorator($decorator): self
    {
        switch (true) {
            case $decorator instanceof AbstractDecorator:
                break;

            case is_string($decorator) && is_subclass_of($decorator, AbstractDecorator::class, true):
                $decorator = new $decorator();
                break;

            case !is_array($decorator):
            case empty($decorator):
                throw new InvalidArgumentException('Invalid decorator passed to addDecorators()');

            case ($candidate = array_shift($decorator)) && is_string($candidate) && is_subclass_of($candidate, AbstractDecorator::class, true):
                $options = array_shift($decorator) ?? [];
                $decorator = new $candidate($options);
                break;
                
            default:
                throw new InvalidArgumentException('Invalid decorator passed to addDecorators()');
        }

        $this->decorators[] = $decorator;
        return $this;
    }

    // Localization:

    /**
     * Indicate whether or not translation should be disabled
     */
    public function setDisableTranslator(bool $flag): self
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

    // metadata

    /**
     * Set form attribute
     *
     * @param string $key
     * @param mixed $value
     */
    public function setAttrib(string $key, $value): self
    {
        $this->attribs[$key] = $value;
        return $this;
    }

    /**
     * Add multiple form attributes at once
     */
    // public function addAttribs(array $attribs): self
    // {
    //     foreach ($attribs as $key => $value) {
    //         $this->setAttrib($key, $value);
    //     }
    //     return $this;
    // }

    /**
     * Set multiple form attributes at once
     *
     * Overwrites any previously set attributes.
     *
     */
    // public function setAttribs(array $attribs): self
    // {
    //     $this->clearAttribs();
    //     return $this->addAttribs($attribs);
    // }

    /**
     * Retrieve a single form attribute
     * 
     * @return mixed
     */
    public function getAttrib(string $key)
    {
        if (!isset($this->attribs[$key])) {
            return null;
        }

        return $this->attribs[$key];
    }

    /**
     * Retrieve all form attributes/metadata
     */
    public function getAttribs(): array
    {
        return $this->attribs;
    }

    /**
     * Remove attribute
     */
    public function removeAttrib(string $key): bool
    {
        if (isset($this->attribs[$key])) {
            unset($this->attribs[$key]);
            return true;
        }
        return false;
    }

    /**
     * Clear all form attributes
     */
    // public function clearAttribs(): void
    // {
    //     $this->attribs = [];
    // }

}

