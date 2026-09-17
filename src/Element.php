<?php declare(strict_types=1);

namespace DalPraS\FormZero;

use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\FormZero\Decorator\DecoratorPreset;
use DalPraS\FormZero\Element\Traits\FiltersTrait;
use DalPraS\FormZero\Factory\FormFactoryInterface;
use DalPraS\FormZero\Filter\FilterChain;
use DalPraS\FormZero\Traits\AttributesTrait;
use DalPraS\FormZero\Traits\ConstraintsTrait;
use DalPraS\FormZero\Traits\ErrorsTrait;
use DalPraS\FormZero\Traits\FormElementTrait;
use DalPraS\FormZero\Traits\RenderTrait;
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
     * Render-only namespace resolved by the owning form.
     *
     * Kept separate from belongsTo because belongsTo is also used by the
     * data-mapping/validation code and must not be rewritten while rendering.
     */
    private ?string $renderBelongsTo = null;

    /** Compiled HTML field path, name and id; rebuilt only after structural changes. */
    private ?FieldPath $compiledFieldPath = null;
    private ?string $compiledFullyQualifiedName = null;
    private ?string $compiledId = null;

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

    /**
     * Cached normalized/filtered value.
     *
     * The cache remains valid while the raw value, array mode and filter-chain
     * revision are unchanged.
     */
    private mixed $filteredValue = null;
    private bool $filteredValueCached = false;
    private ?FilterChain $filteredValueFilterChain = null;
    private int $filteredValueFilterRevision = -1;
    private bool $filteredValueIsArray = false;

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

    public function layout(FormLayout|string $layout): static
    {
        return $this->setLayout($layout);
    }

    public function setLayout(FormLayout|string $layout): static
    {
        $this->setDecorators(DecoratorPreset::forLayout($layout));
        return $this;
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
     * Set element name and invalidate the compiled field path.
     */
    public function setName(string $name): static
    {
        $name = $this->filterName($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Invalid name provided; must contain only valid variable characters and be non-empty');
        }

        $this->name = $name;
        $this->invalidateCompiledFieldPath();
        return $this;
    }

    /**
     * Get fully qualified name
     * Places name as subitem of array and/or appends brackets.
     */
    public function getFullyQualifiedName(): string
    {
        $this->compileFieldPath();
        return $this->compiledFullyQualifiedName;
    }

    /**
     * Get element id
     */
    public function getId(): string
    {
        $this->compileFieldPath();
        return $this->compiledId;
    }

    private function compileFieldPath(): void
    {
        if ($this->compiledFieldPath !== null) {
            return;
        }

        $belongsTo = $this->renderBelongsTo ?? $this->getBelongsTo();
        $this->compiledFieldPath = FieldPath::fromString($belongsTo)->append($this->getName());
        $this->compiledFullyQualifiedName = $this->compiledFieldPath->toString() . ($this->isArray() ? '[]' : '');
        $this->compiledId = $this->compiledFieldPath->toId();
    }

    private function invalidateCompiledFieldPath(): void
    {
        $this->compiledFieldPath = null;
        $this->compiledFullyQualifiedName = null;
        $this->compiledId = null;
    }

    /**
     * Set element value
     */
    public function setValue($value): static
    {
        $this->value = $value;
        $this->filteredValueCached = false;
        return $this;
    }

    /**
     * Retrieve filtered element value
     *
     * @return mixed
     */
    public function getValue()
    {
        $filterChain = $this->getFilterChain();
        $filterRevision = $filterChain->getRevision();
        $isArray = $this->isArray();

        if (
            $this->filteredValueCached
            && $this->filteredValueFilterChain === $filterChain
            && $this->filteredValueFilterRevision === $filterRevision
            && $this->filteredValueIsArray === $isArray
        ) {
            return $this->filteredValue;
        }

        $values = $this->value;

        if ($isArray && is_array($values)) {
            array_walk_recursive($values, static function (&$value) use ($filterChain): void {
                $value = $filterChain->filter($value);
            });
        } else {
            $values = $filterChain->filter($values);
        }

        $this->filteredValue = $values;
        $this->filteredValueCached = true;
        $this->filteredValueFilterChain = $filterChain;
        $this->filteredValueFilterRevision = $filterRevision;
        $this->filteredValueIsArray = $isArray;

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
     * Value used by renderers.
     *
     * Most elements render their normalized value; special elements such as
     * CSRF hashes can override this without mutating the submitted/data value.
     */
    public function getRenderValue(): mixed
    {
        return $this->getValue();
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
     * Set the validation message used by the implicit NotBlank constraint.
     */
    public function setRequiredMessage(string $message): static
    {
        $this->options['requiredMessage'] = $message;
        return $this;
    }

    /**
     * Retrieve the validation message used by the implicit NotBlank constraint.
     */
    public function getRequiredMessage(): ?string
    {
        return $this->options['requiredMessage'] ?? null;
    }

    /**
     * Return the id used by the validation feedback associated with this field.
     */
    public function getValidationFeedbackId(): string
    {
        $controlId = (string) ($this->getAttrib('id') ?? $this->getId());
        return $controlId . '-feedback';
    }

    /**
     * Add the ARIA state and relationship required by an invalid field.
     * Existing aria-describedby references are preserved.
     */
    public function applyValidationAccessibility(array $attributes): array
    {
        if (!$this->hasErrors()) {
            return $attributes;
        }

        $attributes['aria-invalid'] = 'true';

        $describedBy = preg_split(
            '/\s+/',
            trim((string) ($attributes['aria-describedby'] ?? '')),
            -1,
            PREG_SPLIT_NO_EMPTY
        ) ?: [];

        $describedBy[] = $this->getValidationFeedbackId();
        $attributes['aria-describedby'] = implode(' ', array_unique($describedBy));

        return $attributes;
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
        $this->invalidateCompiledFieldPath();
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
            $this->invalidateCompiledFieldPath();
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
     * Set the namespace used only for rendering the fully-qualified HTML name.
     *
     * @internal Managed by ZeroForm when form/subform structure changes.
     */
    public function setRenderBelongsTo(?string $array): static
    {
        $this->renderBelongsTo = $array === null
            ? null
            : $this->filterName($array, true);
        $this->invalidateCompiledFieldPath();

        return $this;
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

        // Required elements get one implicit NotBlank constraint. Preserve an
        // explicitly configured NotBlank constraint instead of adding another.
        if ($this->isRequired() && !$this->hasConstraint(Assert\NotBlank::class)) {
            $requiredMessage = $this->getRequiredMessage();
            $this->prependConstraint($requiredMessage === null
                ? new Assert\NotBlank()
                : new Assert\NotBlank([
                    'message' => $this->getRequiredMessage(),
                ])
            );
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
    public function __toString(): string
    {
        return $this->render();
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

}
