<?php declare(strict_types=1);

namespace DalPraS\FormZero;

use Throwable;
use DalPraS\FormZero\Traits\ErrorsTrait;
use DalPraS\FormZero\Traits\RenderTrait;
use DalPraS\FormZero\Traits\AttributesTrait;
use DalPraS\FormZero\Traits\ConstraintsTrait;
use DalPraS\FormZero\Traits\FormElementTrait;
use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\FormZero\Element\Traits\FiltersTrait;
use DalPraS\FormZero\Factory\FormFactoryInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidator;

class Element implements ElementInterface
{
    use FormElementTrait;
    use AttributesTrait;
    use ErrorsTrait;
    use RenderTrait;
    use ConstraintsTrait;
    use FiltersTrait;

    private ?FormFactoryInterface $factory = null;

    private array $options = [];

    /**
     * Array to which element belongs
     */
    private string $belongsTo = '';

    /**
     * Is the error marked as in an invalid state?
     */
    protected bool $isError = false;

    /**
     * Indica se è stato richiamato il metodo isValid.
     */
    protected bool $isValidated = false;

    /**
     * Formatted validation error messages
     */
    protected array $messages = [];

    /**
     * Element value
     */
    protected $value;

    public function setFactory(FormFactoryInterface $factory): static
    {
        $this->factory = $factory;
        return $this;
    }

    public function getFactory(): FormFactoryInterface
    {
        return $this->factory;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Initialize object; used by extending classes
     */
    public function init(): void
    {

    }

    // Metadata

    /**
     * Set element label
     */
    public function setLabel(string $label): static
    {
        $this->options['label'] = $label;
        return $this;
    }

    /**
     * Retrieve element label
     */
    public function getLabel(): string
    {
        return $this->options['label'] ?? '';
    }

    /**
     * Used to resolve and return an element ID
     * Passed to the HtmlTag decorator as a callback in order to provide an ID.
     */
    public static function resolveElementId(AbstractDecorator $decorator): string
    {
        return $decorator->getElement()->getId() . '-element';
    }

    /**
     * Set object state from options array
     */
    public function initOptions(array $options): static
    {
        if (isset($options['disableTranslator'])) {
            $this->setDisableTranslator($options['disableTranslator']);
            unset($options['disableTranslator']);
        }

        unset($options['options']);
        unset($options['config']);

        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                // Setter exists; use it
                $this->$method($value);
            } else {
                // Assume it's metadata
                $this->setAttrib($key, $value);
            }
        }
        return $this;
    }

    /**
     * Get fully qualified name
     * Places name as subitem of array and/or appends brackets.
     */
    public function getFullyQualifiedName(): string
    {
        $name = $this->getName();
        $belongsTo = $this->getBelongsTo();
        if ($belongsTo !== '') {
            $name = $belongsTo . '[' . $name . ']';
        }
        if ($this->isArray()) {
            $name .= '[]';
        }
        return $name;
    }

    /**
     * Get element id
     */
    public function getId(): string
    {
        $id = $this->getFullyQualifiedName();

        // Bail early if no array notation detected
        if (!strstr($id, '[')) {
            return $id;
        }

        // Strip array notation
        if ('[]' == substr($id, -2)) {
            $id = substr($id, 0, strlen($id) - 2);
        }
        $id = str_replace('][', '-', $id);
        $id = str_replace(array(']', '['), '-', $id);
        $id = trim($id, '-');

        return $id;
    }

    /**
     * Set element value
     */
    public function setValue($value): static
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Retrieve filtered element value
     *
     * @return mixed
     */
    public function getValue()
    {
        $values = $this->value;

        if ($this->isArray() && is_array($values)) {
            array_walk_recursive($values, function (&$value) {
                $value = $this->getFilterChain()->filter($value);
            });
        } else {
            $values = $this->getFilterChain()->filter($values);
        }
        return $values;
    }

    /**
     * Retrieve unfiltered element value
     *
     * @return mixed
     */
    public function getUnfilteredValue()
    {
        return $this->value;
    }

    /**
     * Set required flag
     */
    public function setRequired(bool $required = true): static
    {
        $this->options['required'] = $required;
        return $this;
    }

    /**
     * Is the element required?
     */
    public function isRequired(): bool
    {
        return $this->options['required'] ?? false;
    }

    /**
     * Set element description
     */
    public function setDescription(string $description): static
    {
        $this->options['description'] = $description;
        return $this;
    }

    /**
     * Retrieve element description
     */
    public function getDescription(): string
    {
        return $this->options['description'] ?? '';
    }

    /**
     * Set 'allow empty' flag
     * When the allow empty flag is enabled and the required flag is false, the
     * element will validate with empty values.
     */
    public function setAllowEmpty(bool $allowEmpty): static
    {
        $this->options['allowEmpty'] = $allowEmpty;
        return $this;
    }

    /**
     * Get 'allow empty' flag
     */
    public function getAllowEmpty(): bool
    {
        return $this->options['allowEmpty'] ?? true;
    }

    /**
     * Set ignore flag (used when retrieving values at form level)
     */
    public function setIgnore(bool $ignore): static
    {
        $this->options['ignore'] = $ignore;
        return $this;
    }

    /**
     * Get ignore flag (used when retrieving values at form level)
     */
    public function getIgnore(): bool
    {
        return $this->options['ignore'] ?? false;
    }

    /**
     * Set flag indicating if element represents an array
     */
    public function setIsArray(bool $isArray): static
    {
        $this->options['isArray'] = $isArray;
        return $this;
    }

    /**
     * Is the element representing an array?
     */
    public function isArray(): bool
    {
        return $this->options['isArray'] ?? false;
    }

    /**
     * Was called isValid?
     */
    public function isValidated(): bool
    {
        return $this->isValidated;
    }

    /**
     * Set array to which element belongs
     */
    public function setBelongsTo(string $array): static
    {
        $array = $this->filterName($array, true);
        if ($array !== '') {
            $this->belongsTo = $array;
        }
        return $this;
    }

    /**
     * Return array name to which element belongs
     */
    public function getBelongsTo(): string
    {
        return $this->belongsTo;
    }

    /**
     * Return element type
     */
    public function getType(): string
    {
        return $this->options['type'] ?? '';
    }

    public function setType(string $type): static
    {
        $this->options['type'] = $type;
        return $this;
    }

    // Validation
    protected function isEmpty($value): bool
    {
        return $value === '' || $value === null;
    }

    /**
     * Validate element value using Symfony Validator only.
     *
     * @param mixed $value
     * @param mixed $context  (kept for BC, not used by Symfony)
     */
    public function isValid($value, $context = null): bool
    {
        $this->isValidated = true;
        $this->setValue($value);
        $value = $this->getValue(); // filtered value

        // reset state
        $this->messages = [];
        $this->isError  = false;

        if ($this->isEmpty($value)) {
            if (!$this->isRequired() && $this->getAllowEmpty()) {
                // optional and empty → valid
                return true;
            }

            // required and empty → let NotBlank handle message
        }

        /** @var SymfonyValidator $symfonyValidator */
        $symfonyValidator = $this->getFactory()->getValidator();

        // If required and not allowEmpty, prepend a NotBlank
        if ($this->isRequired()) {
            $this->prependConstraint(new Assert\NotBlank());
        }

        $result = true;

        // Validate array vs scalar
        if ($this->isArray() && is_array($value)) {
            foreach ($value as $val) {
                $violations = $symfonyValidator->validate($val, $this->getConstraints());
                if (count($violations) > 0) {
                    $result = false;
                    foreach ($violations as $violation) {
                        $this->messages[] = $violation->getMessage();
                    }
                }
            }
        } else {
            $violations = $symfonyValidator->validate($value, $this->getConstraints());
            if (count($violations) > 0) {
                $result = false;
                foreach ($violations as $violation) {
                    $this->messages[] = $violation->getMessage();
                }
            }
        }

        if ($this->isError) {
            return false;
        }

        return $result;
    }

    /**
     * Get errorMessageSeparator
     */
    public function getErrorMessageSeparator(): string
    {
        return $this->options['errorMessageSeparator'] ?? '; ';
    }

    /**
     * Set errorMessageSeparator
     */
    public function setErrorMessageSeparator(string $separator): static
    {
        $this->options['errorMessageSeparator'] = $separator;
        return $this;
    }

    /**
     * Mark the element as being in a failed validation state
     */
    public function markAsError(): void
    {
        $messages = $this->getMessages() + $this->getFormattedErrorMessages();
        if (empty($messages)) {
            $this->isError = true;
        } else {
            $this->messages = $messages;
        }
    }

    /**
     * Are there errors registered?
     */
    public function hasErrors(): bool
    {
        return (!empty($this->messages) || $this->isError);
    }

    /**
     * Retrieve error messages
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    // Rendering

    /**
     * String representation of form element
     *
     * Proxies to {@link render()}.
     *
     * @return string
     */
    public function __toString()
    {
        try {
            $return = $this->render();
            return $return;
        } catch (Throwable $th) {
            trigger_error($th->getMessage(), E_USER_WARNING);
            return '';
        }
    }

    /**
     * Retrieve error messages and perform translation and value substitution
     */
    protected function getFormattedErrorMessages(): array
    {
        $messages = $this->getErrorMessages();
        $value    = $this->getValue();
        foreach ($messages as $key => $message) {
            if ($this->isArray() && is_array($value)) {
                $aggregateMessages = [];
                foreach ($value as $val) {
                    $aggregateMessages[] = str_replace('%value%', $val, $message);
                }
                if (count($aggregateMessages)) {
                    $messages[$key] = implode($this->getErrorMessageSeparator(), $aggregateMessages);
                }
            } else {
                $messages[$key] = str_replace('%value%', (string) $value, $message);
            }
        }
        return $messages;
    }

    // Rendering

    /**
     * Render form
     */
    // public function render(): string
    // {
    //     $content = '';
    //     /** @var \DalPraS\FormZero\Decorator\AbstractDecorator $decorator */
    //     foreach ($this->getDecorators() as $decorator) {
    //         $decorator->setElement($this);
    //         $content = $decorator->render($content);
    //     }
    //     return $content;
    // }
}
