<?php declare(strict_types=1);

namespace DalPraS\FormZero;

use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\FormZero\Factory\FormFactoryInterface;
use DalPraS\FormZero\Traits\FormElementTrait;
use InvalidArgumentException;
use Laminas\Filter\FilterChain;
use Laminas\Filter\FilterInterface;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\ValidatorChain;
use Laminas\Validator\ValidatorInterface;
use Throwable;

class Element implements ValidatorInterface, ElementInterface
{
    use FormElementTrait;

    private ?FormFactoryInterface $factory = null;

    private array $options = [];

    /**
     * Array to which element belongs
     */
    private string $belongsTo = '';

    /**
     * Filters to apply to element
     */
    private ?FilterChain $filterChain = null;

    /**
     * Validators to apply to element
     */
    private ?ValidatorChain $validatorChain = null;

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
     * Array of initialized validators
     */
    private array $validators = [];

    /**
     * Element value
     */
    protected $value;

    public function __construct() {}

    public function setFactory(FormFactoryInterface $factory): self
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
    public function setLabel(string $label): self
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
    public function initOptions(array $options): self
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
    public function setValue($value): self
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
    public function setRequired(bool $required = true): self
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
    public function setDescription(string $description): self
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
    public function setAllowEmpty(bool $allowEmpty): self
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
    public function setIgnore(bool $ignore): self
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
    public function setIsArray(bool $isArray): self
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
    public function setBelongsTo(string $array): self
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

    public function setType(string $type): self
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
     * Validate element value
     *
     * If a translation adapter is registered, any error messages will be
     * translated according to the current locale, using the given error code;
     * if no matching translation is found, the original message will be
     * utilized.
     *
     * Note: The *filtered* value is validated.
     *
     * @param mixed $value
     * @param mixed $context
     * @return boolean
     */
    public function isValid($value, $context = null): bool
    {
        $this->isValidated = true;
        $this->setValue($value);
        $value = $this->getValue();

        if ( $this->isEmpty($value) && !$this->isRequired() && $this->getAllowEmpty() ) {
            return true;
        }

        if ($this->isRequired()) {
            $this->getValidatorChain()->prependByName(NotEmpty::class, [], true);
        }
        // nel caso di più validazioni, resettiamo sempre il valore
        $this->messages = [];
        
        // assume that isvalid
        $result = true;
        $helpers = $this->getFactory()->getTemplate()->getHelpers();

        foreach ($this->getValidatorChain() as $validator) {
            /** @var array{instance:\Laminas\Validator\AbstractValidator,breakChainOnFailure:bool} $validator */
            $validator['instance']->setTranslator($helpers->translator());

            if ($this->isArray() && is_array($value)) {
                $messages = [];
                if (empty($value)) {
                    if ($this->isRequired() || (!$this->isRequired() && !$this->getAllowEmpty()) ) {
                        $value = '';
                    }
                }
                foreach ( (array) $value as $val ) {
                    // in case of failures it is not valid
                    if ( $validator['instance']->isValid($val, $context) === false ) {
                        $result = false;
                        if ($this->hasErrorMessages()) {
                            $messages = $this->_getErrorMessages();
                        } else {
                            $messages = array_merge($messages, $validator['instance']->getMessages());
                        }
                    }
                }
                if ($result) {
                    continue;
                }
            } elseif ( $validator['instance']->isValid($value, $context) === true ) {
                continue;
            } else {
                $result = false;
                if ($this->hasErrorMessages()) {
                    $messages = $this->_getErrorMessages();
                } else {
                    $messages = $validator['instance']->getMessages();
                }
            }

            $result = false;
            // ciclicamente aggiunge i messaggi
            $this->messages = array_merge($this->messages, $messages);

            if ($validator['breakChainOnFailure'] === true) {
                break;
            }
        }

        // If element manually flagged as invalid, return false
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
    public function setErrorMessageSeparator(string $separator): self
    {
        $this->options['errorMessageSeparator'] = $separator;
        return $this;
    }

    /**
     * Mark the element as being in a failed validation state
     */
    public function markAsError(): self
    {
        $messages = $this->getMessages() + $this->_getErrorMessages();
        if (empty($messages)) {
            $this->isError = true;
        } else {
            $this->messages = $messages;
        }
        return $this;
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

    // Filtering

    /**
     * Add filters to element
     */
    public function addFilters(array $filters): void
    {
        $filterChain = $this->getFilterChain();
        foreach ($filters as $filterInfo) {
            switch (true) {
                case is_string($filterInfo):
                    $filterChain->attachByName($filterInfo);
                    break;

                case is_array($filterInfo):
                    $filterChain->attachByName(...$filterInfo);
                    break;

                default:
                    throw new InvalidArgumentException('Invalid filter passed to addFilters()');
            }
        }
    }

    /**
     * Add filters to element, overwriting any already existing
     *
     * @param array|\Laminas\Filter\FilterInterface[] $filters
     */
    public function setFilters(array $filters): self
    {
        $this->clearFilterChain();
        $this->addFilters($filters);
        return $this;
    }

    /**
     * Retrieve a single filter by name
     */
    public function getFilter(string $name): ?FilterInterface
    {
        /** @var \Laminas\Stdlib\PriorityQueue $filters */
        $filters = $this->getFilterChain()->getFilters();
        foreach ($filters as $filter) {
            if ( get_class($filter) === $name ) {
                return $filter;
            }
        }
        return null;
    }

    public function getFilterChain(): FilterChain
    {
        if ($this->filterChain === null) {
            $this->filterChain = new FilterChain();
        }
        return $this->filterChain;
    }

    public function clearFilterChain(): void
    {
        $this->filterChain = null;
    }


    // Validator

    public function addValidators(array $validators): void
    {
        $validatorChain = $this->getValidatorChain();
        foreach ($validators as $validatorInfo) {
            $validatorChain->attachByName(...$validatorInfo);
        }
    }

    public function getValidatorChain(): ValidatorChain
    {
        if ($this->validatorChain === null) {
            $this->validatorChain = new ValidatorChain();
        }
        return $this->validatorChain;
    }

    /**
     * Add validators to element, overwriting any already existing
     */
    public function setValidators(array $validators): void
    {
        $this->clearValidatorChain();
        $this->addValidators($validators);
    }

    /**
     * Retrieve a single validator by name
     */
    public function getValidator(string $name): array
    {
        foreach ($this->getValidatorChain() as $validator) {
            if ( get_class($validator['instance']) === $name ) {
                return $validator;
            }
        }
        return [];
    }

    /**
     * Clear all validators
     */
    public function clearValidatorChain(): void
    {
        $this->validatorChain = null;
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
    protected function _getErrorMessages(): array
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
    public function render(): string
    {
        $content = '';
        /** @var \DalPraS\FormZero\Decorator\AbstractDecorator $decorator */
        foreach ($this->getDecorators() as $decorator) {
            $decorator->setElement($this);
            $content = $decorator->render($content);
        }
        return $content;
    }
}
