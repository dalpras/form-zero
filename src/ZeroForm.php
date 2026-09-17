<?php declare(strict_types=1);

namespace DalPraS\FormZero;

use DalPraS\FormZero\Decorator\ElementsDecorator;
use DalPraS\FormZero\Decorator\FormDecorator;
use DalPraS\FormZero\Element\Intefaces\MultiChoicesInterface;
use DalPraS\FormZero\Factory\FormFactoryInterface;
use DalPraS\FormZero\Traits\AttributesTrait;
use DalPraS\FormZero\Traits\ErrorsTrait;
use DalPraS\FormZero\Traits\FormElementTrait;
use DalPraS\FormZero\Traits\RenderTrait;
use DalPraS\SmartTemplate\Plugins\HelpersInterface;
use InvalidArgumentException;
use LogicException;

class ZeroForm extends ElementsOrdered
{
    use FormElementTrait;
    use AttributesTrait;
    use ErrorsTrait;
    use RenderTrait;

    private string $description = '';

    private array $elements = [];

    private string $elementsBelongTo = '';

    /** Compiled local path used for data mapping. */
    private ?FieldPath $elementsBelongToPath = null;

    /** Compiled parent namespace used only to resolve HTML field names. */
    private ?FieldPath $renderParentBelongsToPath = null;

    /** @var array<string, FieldPath> Parsed paths reused by data-mapping helpers. */
    private array $fieldPaths = [];

    private ?FormDataMapper $formDataMapper = null;

    private int $order = 0;

    private string $legend = '';

    private array $subForms = [];

    protected bool $isArray = false;

    protected bool $errorsExist = false;

    protected HelpersInterface $helpers;

    protected FormFactoryInterface $factory;

    public function __construct(FormFactoryInterface $factory) 
    {
        $this->factory = $factory;
        $this->helpers = $this->factory->template()->getHelpers();
    }

    /**
     * Initialize form (used by extending classes)
     */
    public function init(): void
    {

    }

    /**
     * Set form name and invalidate a name-derived array path.
     */
    public function setName(string $name): static
    {
        $name = $this->filterName($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Invalid name provided; must contain only valid variable characters and be non-empty');
        }

        $this->name = $name;
        if ($this->elementsBelongTo === '') {
            $this->elementsBelongToPath = null;
        }
        return $this;
    }

    /**
     * Get fully qualified name
     *
     * Places name as subitem of array and/or appends brackets.
     */
    public function getFullyQualifiedName(): string
    {
        return $this->getName();
    }

    public function loadDefaultDecorators(): static
    {
        $decorators = $this->getDecorators();
        if (empty($decorators)) {
            $this->addDecorators([
                [ElementsDecorator::class],
                [FormDecorator::class]
            ]);
        }
        return $this;
    }

    public function __clone()
    {
        throw new LogicException('Not allowed to clone form!');
    }

    /**
     * Reset values of form
     */
    public function reset()
    {
        /** @var \DalPraS\FormZero\Element $element */
        foreach ($this->getElements() as $element) {
            $element->setValue(null);
        }
        /** @var \DalPraS\FormZero\SubZeroForm $subForm */
        foreach ($this->getSubForms() as $subForm) {
            $subForm->reset();
        }

        return $this;
    }


    /**
     * Get element id
     */
    public function getId(): string
    {
        if ( ($attrid = $this->getAttrib('id')) !== null) {
            return $attrid;
        }
        
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
     * Set form legend
     */
    public function setLegend(string $value): static
    {
        $this->legend = $value;
        return $this;
    }

    /**
     * Get form legend
     */
    public function getLegend(): string
    {
        return $this->legend;
    }

    /**
     * Set form description
     */
    public function setDescription(string $value): static
    {
        $this->description = $value;
        return $this;
    }

    /**
     * Retrieve form description
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Set visualization order
     */
    public function setOrder(int $index): static
    {
        $this->order = $index;
        return $this;
    }

    /**
     * Get form order
     */
    public function getOrder(): int
    {
        return $this->order;
    }


    // Element interaction:

    /**
     * Retrieve a single element
     */
    public function getElement(string $name): ElementInterface|null
    {
        if (isset($this->elements[$name])) {
            return $this->elements[$name];
        }
        return null;
    }

    public function getMultiElement(string $name): MultiChoicesInterface|null
    {
        return $this->getElement($name);
    }

    /**
     * Aggiunge un elemento alla form e ritorna l'elemento creato.
     *
     * Acts as a factory shortcut for creating an element and attaching it to
     * the form. The returned element is the same instance stored in the form,
     * after ordering and belongs-to propagation have been applied.
     */
    public function add(ElementInterface|string $element, string $name, array $options = [], ?int $order = null): ElementInterface
    {
        /** @var \DalPraS\FormZero\ElementInterface $element */
        $element = $this->factory->createElement($element, $name, $options);
        $this->addElement($element, $order);

        return $element;
    }

    public function addElement(ElementInterface $element, ?int $order = null): static
    {
        $name = $element->getName();
        if (isset($this->elements[$name]) || isset($this->subForms[$name])) {
            throw new InvalidArgumentException("Impossible to add \"{$name}\" element that already exists");
        }
        $this->elements[$name] = $element;

        $this->set($name, $order ?? ($this->last() + 1));
        if ($order !== null) {
            $this->sort();
        }

        $this->applyBelongsTo($name);
        $this->refreshRenderContext();
        return $this;
    }

    /**
     * Check element existance
     */
    public function hasElement(string $name): bool
    {
        return array_key_exists($name, $this->elements);
    }

    /**
     * Retrieve all elements
     */
    public function getElements(): array
    {
        return $this->elements;
    }

    /**
     * Replace an existing element (by name) with a new one.
     */
    public function replaceElement(ElementInterface|string $element, string $name, array $options = []): ElementInterface
    {
        // If a subForm already exists with this name, throw exception or handle as you wish
        if (isset($this->subForms[$name])) {
            throw new InvalidArgumentException(
                sprintf('SubForm with name "%s" exists; cannot replace with an element.', $name)
            );
        }
        $order = $this->get($name);
        // Remove the existing element if present
        $this->removeElement($name);

        $element = $this->factory->createElement($element, $name, $options);
        $this->addElement($element, $order);
        return $element;
    }

    /**
     * Remove element
     */
    public function removeElement(string $name): bool
    {
        $name = (string) $name;
        if (isset($this->elements[$name])) {
            unset($this->elements[$name]);
            $this->del($name);
            return true;
        }

        return false;
    }

    /**
     * Remove all form elements
     */
    public function clearElements(): static
    {
        foreach (array_keys($this->elements) as $key) {
            $this->del($key);
        }
        $this->elements = [];
        return $this;
    }

    /**
     * Set default values for elements
     * Sets values for all elements specified in the array of $defaults.
     */
    public function setDefaults(array $defaults): static
    {
        $this->dataMapper()->setDefaults($this, $defaults);
        return $this;
    }

    /**
     * Set default value for an element
     */
    public function setDefault(string $name, $value): static
    {
        if ($element = $this->getElement($name)) {
            $element->setValue($value);
        } else {
            if (is_scalar($value)) {
                /** @var \DalPraS\FormZero\SubZeroForm $subForm */
                foreach ($this->getSubForms() as $subForm) {
                    $subForm->setDefault($name, $value);
                }
            } elseif (is_array($value) && ($subForm = $this->getSubForm($name))) {
                $subForm->setDefaults($value);
            }
        }
        return $this;
    }

    /**
     * Retrieve value for single element
     *
     * @return mixed|null
     */
    public function getValue(string $name)
    {
        if ($element = $this->getElement($name)) {
            return $element->getValue();
        }

        if ($subForm = $this->getSubForm($name)) {
            return $subForm->getValues(true);
        }

        /** @var \DalPraS\FormZero\SubZeroForm $subForm */
        foreach ($this->getSubForms() as $subForm) {
            if ($name == $subForm->getElementsBelongTo()) {
                return $subForm->getValues(true);
            }
        }
        return null;
    }

    /**
     * Retrieve all form element values
     */
    public function getValues(bool $suppressArrayNotation = false): array
    {
        return $this->dataMapper()->getValues($this, $suppressArrayNotation);
    }

    /**
     * Returns only the valid values from the given form input.
     *
     * For models that can be saved in a partially valid state, for example when following the builder,
     * prototype or state patterns it is particularly interessting to retrieve all the current valid
     * values to persist them.
     */
    public function getValidValues(array $data, $suppressArrayNotation = false): array
    {
        return $this->dataMapper()->getValidValues($this, $data, (bool) $suppressArrayNotation);
    }

    /**
     * Set all elements' filters
     */
    public function setElementFilters(array $filters): static
    {
        /** @var \DalPraS\FormZero\Element $element */
        foreach ($this->getElements() as $element) {
            $element->setFilters($filters);
        }
        return $this;
    }

    /**
     * Set name of array elements belong to
     */
    public function setElementsBelongTo(string $array): static
    {
        $origName = $this->getElementsBelongTo();
        $belongsTo = $this->filterName($array, true);
        $this->elementsBelongTo = $belongsTo;
        $this->elementsBelongToPath = $this->fieldPath($belongsTo);

        if ($belongsTo === '') {
            $this->isArray = false;
            if ($origName !== '') {
                $this->applyBelongsTo();
            }
        } else {
            $this->isArray = true;
            $this->applyBelongsTo();
        }

        $this->refreshRenderContext();
        return $this;
    }

    /**
     * Set array to which elements belong
     */
    private function applyBelongsTo(string $name = ''): void
    {
        // carica il nome dell'array cui appartengono gli elementi di questa form
        $array = $this->getElementsBelongTo();

        switch (true) {
            // se non è specificato il nome dell'array esce
            case $array === '':
                break;

            // se non è specificato il nome dell'elemento, ma il nome dell'array è definito
            case $name === '':
                foreach ($this->getElements() as $element) {
                    $element->setBelongsTo($array);
                }
                break;

            // se il nome indicato corrisponde ad un elemento, associalo all'array
            case ($element = $this->getElement($name)) !== null:
                $element->setBelongsTo($array);
        }
    }

    /**
     * Resolve render-only namespaces without changing the logical data mapping.
     *
     * A subform keeps its local elementsBelongTo (for values/validation), while
     * its elements receive the fully-qualified parent path used for HTML names.
     */
    private function refreshRenderContext(?FieldPath $parentBelongsTo = null): void
    {
        if ($parentBelongsTo !== null) {
            $this->renderParentBelongsToPath = $parentBelongsTo;
        }

        $localBelongsTo = $this->isArray() ? $this->getElementsBelongToPath() : $this->fieldPath('');
        $effectiveBelongsTo = $localBelongsTo;

        if ($this->renderParentBelongsToPath !== null && !$this->renderParentBelongsToPath->isEmpty()) {
            $effectiveBelongsTo = $localBelongsTo->isEmpty()
                ? $this->renderParentBelongsToPath
                : $this->renderParentBelongsToPath->append($localBelongsTo);
        }

        $renderBelongsTo = $effectiveBelongsTo->isEmpty() ? null : $effectiveBelongsTo->toString();

        foreach ($this->getElements() as $element) {
            if ($element instanceof Element) {
                $element->setRenderBelongsTo($renderBelongsTo);
            }
        }

        foreach ($this->getSubForms() as $subForm) {
            $subForm->refreshRenderContext($effectiveBelongsTo);
        }
    }

    private function getElementsBelongToPath(): FieldPath
    {
        return $this->elementsBelongToPath ??= $this->fieldPath($this->getElementsBelongTo());
    }

    private function dataMapper(): FormDataMapper
    {
        return $this->formDataMapper ??= new FormDataMapper();
    }

    private function fieldPath(string $path): FieldPath
    {
        return $this->fieldPaths[$path] ??= FieldPath::fromString($path);
    }

    /**
     * Get name of array elements belong to
     */
    public function getElementsBelongTo(): string
    {
        if ( $this->elementsBelongTo === '' && $this->isArray() ) {
            $name = $this->getName();
            if ( (string) $name !== '' ) {
                return $name;
            }
        }
        return $this->elementsBelongTo;
    }

    /**
     * Set flag indicating elements belong to array
     */
    public function setIsArray(bool $flag): static
    {
        $this->isArray = $flag;
        if ($this->elementsBelongTo === '') {
            $this->elementsBelongToPath = null;
        }
        $this->refreshRenderContext();
        return $this;
    }

    /**
     * Get flag indicating if elements belong to an array
     */
    public function isArray(): bool
    {
        return $this->isArray;
    }

    // Element groups:

    /**
     * Crea una subform
     */
    public function createSubZeroForm(): SubZeroForm
    {
        return $this->factory->createForm(SubZeroForm::class);
    }

    /**
     * Add a form group/subform
     */
    public function addSubForm(SubZeroForm $subForm, $name, ?int $order = null): static
    {
        $oldName = $subForm->getName();

        if ( $oldName && $oldName !== $name && $oldName === $subForm->getElementsBelongTo()) {
            $subForm->setElementsBelongTo($name);
        }

        if (isset($this->elements[$name]) || isset($this->subForms[$name])) {
            throw new InvalidArgumentException("Impossible to add \"{$name}\" element that already exists");
        }

        $subForm->setName((string) $name);
        $this->subForms[$name] = $subForm;
        $this->set($name, $order ?? ($this->last() + 1));
        if ($order !== null) {
            $this->sort();
        }
        $this->refreshRenderContext();
        return $this;
    }

    /**
     * Retrieve a form subForm/subform
     */
    public function getSubForm(string $name): ?SubZeroForm
    {
        if (isset($this->subForms[$name])) {
            return $this->subForms[$name];
        }
        return null;
    }

    /**
     * Retrieve all form subForms/subforms
     */
    public function getSubForms(): array
    {
        return $this->subForms;
    }

    /**
     * Remove form subForm/subform
     */
    public function removeSubForm(string $name): bool
    {
        if (array_key_exists($name, $this->subForms)) {
            unset($this->subForms[$name]);
            $this->del($name);
            return true;
        }
        return false;
    }

    /**
     * Remove all form subForms/subforms
     */
    public function clearSubForms(): static
    {
        foreach (array_keys($this->subForms) as $key) {
            $this->del($key);
        }
        $this->subForms = [];
        return $this;
    }

    // Processing

    /**
     * Extract the value by walking the array using given array path.
     * Given an array path such as foo[bar][baz], returns the value of the last
     * element (in this case, 'baz').
     *
     * @param array $value Array to walk
     * @param string $arrayPath Array notation path of the part to extract
     * @return string|array
     */
    private function dissolveArrayValue(array $value, string $arrayPath)
    {
        return $this->fieldPath($arrayPath)->read($value);
    }


    /**
     * Given an array, an optional arrayPath and a key this method
     * dissolves the arrayPath and unsets the key within the array
     * if it exists.
     */
    private function dissolveArrayUnsetKey(array $array, ?string $arrayPath, string $key): array
    {
        return $this->fieldPath((string) $arrayPath)->remove($array, $key);
    }


    /**
     * Converts given arrayPath to an array and attaches given value at the end of it.
     *
     * @param mixed $value The value to attach
     * @param string $arrayPath Given array path to convert and attach to.
     * @return array
     */
    private function attachToArray($value, $arrayPath)
    {
        return $this->fieldPath((string) $arrayPath)->wrap($value);
    }


    /**
     * Validate the form
     *
     * @param array $data
     * @return bool
     */
    public function isValid($data): bool
    {
        if (!is_array($data)) {
            throw new \InvalidArgumentException(__METHOD__ . ' expects an array');
        }
        $valid      = true;
        $eBelongTo  = null;

        if ($this->isArray()) {
            $eBelongTo = $this->getElementsBelongTo();
            $data = $this->dissolveArrayValue($data, $eBelongTo);
        }
        $context = $data;

        /** @var \DalPraS\FormZero\Element $element */
        foreach ($this->getElements() as $key => $element) {
            $check = $data;
            if (($belongsTo = $element->getBelongsTo()) !== $eBelongTo) {
                $check = $this->dissolveArrayValue($data, $belongsTo);
            }
            if (!isset($check[$key])) {
                $valid = $element->isValid(null, $context) && $valid;
            } else {
                $valid = $element->isValid($check[$key], $context) && $valid;
                $data = $this->dissolveArrayUnsetKey($data, $belongsTo, $key);
            }
        }
        /** @var \DalPraS\FormZero\SubZeroForm $form */
        foreach ($this->getSubForms() as $key => $form) {
            if (isset($data[$key]) && !$form->isArray()) {
                $valid = $form->isValid($data[$key]) && $valid;
            } else {
                $valid = $form->isValid($data) && $valid;
            }
        }

        $this->errorsExist = !$valid;
        return $valid;
    }

    /**
     * Validate a partial form
     * Does not check for required flags.
     */
    public function isValidPartial(array $data): bool
    {
        $eBelongTo  = null;

        if ($this->isArray()) {
            $eBelongTo = $this->getElementsBelongTo();
            $data = $this->dissolveArrayValue($data, $eBelongTo);
        }

        $valid      = true;
        $context    = $data;

        /** @var \DalPraS\FormZero\Element $element */
        foreach ($this->getElements() as $key => $element) {
            $check = $data;
            if (($belongsTo = $element->getBelongsTo()) !== $eBelongTo) {
                $check = $this->dissolveArrayValue($data, $belongsTo);
            }
            if (isset($check[$key])) {
                $valid = $element->isValid($check[$key], $context) && $valid;
                $data = $this->dissolveArrayUnsetKey($data, $belongsTo, $key);
            }
        }
        /** @var \DalPraS\FormZero\SubZeroForm $form */
        foreach ($this->getSubForms() as $key => $form) {
            if (isset($data[$key]) && !$form->isArray()) {
                $valid = $form->isValidPartial($data[$key]) && $valid;
            } else {
                $valid = $form->isValidPartial($data) && $valid;
            }
        }

        $this->errorsExist = !$valid;
        return $valid;
    }

    /**
     * Mark the element as being in a failed validation state
     */
    public function markAsError(): void
    {
        $this->errorsExist  = true;
    }

    /**
     * Are there errors in the form?
     */
    public function hasErrors(): bool
    {
        $errors = $this->errorsExist;
        if (!$errors) {
            /** @var \DalPraS\FormZero\Element $element */
            foreach ($this->getElements() as $element) {
                if ($element->hasErrors()) {
                    $errors = true;
                    break;
                }
            }

            /** @var \DalPraS\FormZero\SubZeroForm $subForm */
            foreach ($this->getSubForms() as $subForm) {
                if ($subForm->hasErrors()) {
                    $errors = true;
                    break;
                }
            }
        }
        return $errors;
    }

    public function getMessages(): array
    {
        // Returns global form-level error messages only
        $customMessages = $this->getErrorMessages();
        if ($this->hasErrors() && !empty($customMessages)) {
            return $customMessages;
        }

        $messages = [];

        /** @var \DalPraS\FormZero\Element $element */
        foreach ($this->getElements() as $name => $element) {
            $eMessages = $element->getMessages();
            if (!empty($eMessages)) {
                $messages[$name] = $eMessages;
            }
        }

        /** @var \DalPraS\FormZero\SubZeroForm $subForm */
        foreach ($this->getSubForms() as $key => $subForm) {
            $merge = $subForm->getMessagesForElement(null, true);
            if (!empty($merge)) {
                if (!$subForm->isArray()) {
                    $merge = array($key => $merge);
                } else {
                    $merge = $this->attachToArray($merge,
                    $subForm->getElementsBelongTo());
                }
                $messages = array_replace_recursive($messages, $merge);
            }
        }

        if ($this->isArray()) {
            $messages = $this->attachToArray($messages, $this->getElementsBelongTo());
        }

        return $messages;
    }

    /**
     * Retrieve error messages from elements failing validations
     */
    public function getMessagesForElement(?string $name = null, bool $suppress = false): array
    {
        if (null !== $name) {
            if (isset($this->elements[$name])) {
                return $this->elements[$name]->getMessages();
            } else if (isset($this->subForms[$name])) {
                return $this->subForms[$name]->getMessagesForElement(null, true);
            }
            /** @var \DalPraS\FormZero\SubZeroForm $subForm */
            foreach ($this->getSubForms() as $key => $subForm) {
                if ($subForm->isArray()) {
                    $belongTo = $subForm->getElementsBelongTo();
                    if ($name == $this->fieldPath($belongTo)->leaf()) {
                        return $subForm->getMessagesForElement(null, true);
                    }
                }
            }
        }

        $customMessages = $this->getErrorMessages();
        if ($this->hasErrors() && !empty($customMessages)) {
            return $customMessages;
        }

        $messages = [];

        /** @var \DalPraS\FormZero\Element $element */
        foreach ($this->getElements() as $name => $element) {
            $eMessages = $element->getMessages();
            if (!empty($eMessages)) {
                $messages[$name] = $eMessages;
            }
        }

        /** @var \DalPraS\FormZero\SubZeroForm $subForm */
        foreach ($this->getSubForms() as $key => $subForm) {
            $merge = $subForm->getMessagesForElement(null, true);
            if (!empty($merge)) {
                if (!$subForm->isArray()) {
                    $merge = array($key => $merge);
                } else {
                    $merge = $this->attachToArray($merge,
                    $subForm->getElementsBelongTo());
                }
                $messages = array_replace_recursive($messages, $merge);
            }
        }

        if (!$suppress && $this->isArray()) {
            $messages = $this->attachToArray($messages, $this->getElementsBelongTo());
        }

        return $messages;
    }

    /**
     * Whether this form tree contains at least one file-upload element.
     */
    public function requiresMultipartEncoding(): bool
    {
        foreach ($this->getElements() as $element) {
            if ($element instanceof \DalPraS\FormZero\Element\SymfileElement) {
                return true;
            }
        }

        foreach ($this->getSubForms() as $subForm) {
            if ($subForm->requiresMultipartEncoding()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Serialize as string
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * Attempts to get an element or subForm by name.
     *
     * @param string|int $name
     */
    public function getElementOrSubform($name)
    {
        if (isset($this->elements[$name])) {
            return $this->elements[$name];
        }
    
        if (isset($this->subForms[$name])) {
            return $this->subForms[$name];
        }
    
        throw new \InvalidArgumentException('Invalid element or subForm');
    }

    // Rendering

    /**
     * Renders the form.
     */

    public function getFactory(): FormFactoryInterface
    {
        return $this->factory;
    }
}
