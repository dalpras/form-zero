<?php declare(strict_types=1);

namespace DalPraS\FormZero;

/**
 * Internal form-tree validation and message traversal for ZeroForm.
 *
 * Element-level constraint execution remains owned by Element::isValid(); this
 * collaborator only coordinates data paths, subforms and message aggregation.
 *
 * @internal
 */
final class FormValidator
{
    /** @var array<string, FieldPath> */
    private array $paths = [];

    public function isValid(ZeroForm $form, array $data): bool
    {
        $elementsBelongTo = null;

        if ($form->isArray()) {
            $elementsBelongTo = $form->getElementsBelongTo();
            $data = $this->read($data, $elementsBelongTo);
        }

        $valid = true;
        $context = $data;

        /** @var Element $element */
        foreach ($form->getElements() as $key => $element) {
            $check = $data;
            $belongsTo = $element->getBelongsTo();

            if ($belongsTo !== $elementsBelongTo) {
                $check = $this->read($data, $belongsTo);
            }

            if (!isset($check[$key])) {
                $valid = $element->isValid(null, $context) && $valid;
            } else {
                $valid = $element->isValid($check[$key], $context) && $valid;
                $data = $this->remove($data, $belongsTo, (string) $key);
            }
        }

        /** @var SubZeroForm $subForm */
        foreach ($form->getSubForms() as $key => $subForm) {
            if (isset($data[$key]) && !$subForm->isArray()) {
                $valid = $this->isValid($subForm, $data[$key]) && $valid;
            } else {
                $valid = $this->isValid($subForm, $data) && $valid;
            }
        }

        $form->setValidationResult($valid);

        return $valid;
    }

    public function isValidPartial(ZeroForm $form, array $data): bool
    {
        $elementsBelongTo = null;

        if ($form->isArray()) {
            $elementsBelongTo = $form->getElementsBelongTo();
            $data = $this->read($data, $elementsBelongTo);
        }

        $valid = true;
        $context = $data;

        /** @var Element $element */
        foreach ($form->getElements() as $key => $element) {
            $check = $data;
            $belongsTo = $element->getBelongsTo();

            if ($belongsTo !== $elementsBelongTo) {
                $check = $this->read($data, $belongsTo);
            }

            if (isset($check[$key])) {
                $valid = $element->isValid($check[$key], $context) && $valid;
                $data = $this->remove($data, $belongsTo, (string) $key);
            }
        }

        /** @var SubZeroForm $subForm */
        foreach ($form->getSubForms() as $key => $subForm) {
            if (isset($data[$key]) && !$subForm->isArray()) {
                $valid = $this->isValidPartial($subForm, $data[$key]) && $valid;
            } else {
                $valid = $this->isValidPartial($subForm, $data) && $valid;
            }
        }

        $form->setValidationResult($valid);

        return $valid;
    }

    public function messages(ZeroForm $form, bool $suppress = false): array
    {
        $customMessages = $form->getErrorMessages();
        if ($form->hasErrors() && $customMessages !== []) {
            return $customMessages;
        }

        $messages = $this->aggregateMessages($form);

        if (!$suppress && $form->isArray()) {
            $messages = $this->wrap($messages, $form->getElementsBelongTo());
        }

        return $messages;
    }

    public function messagesForElement(
        ZeroForm $form,
        ?string $name = null,
        bool $suppress = false
    ): array {
        if ($name !== null) {
            if (($element = $form->getElement($name)) !== null) {
                return $element->getMessages();
            }

            if (($subForm = $form->getSubForm($name)) !== null) {
                return $this->messagesForElement($subForm, null, true);
            }

            /** @var SubZeroForm $subForm */
            foreach ($form->getSubForms() as $subForm) {
                if ($subForm->isArray()) {
                    $belongsTo = $subForm->getElementsBelongTo();
                    if ($name == $this->path($belongsTo)->leaf()) {
                        return $this->messagesForElement($subForm, null, true);
                    }
                }
            }
        }

        $customMessages = $form->getErrorMessages();
        if ($form->hasErrors() && $customMessages !== []) {
            return $customMessages;
        }

        $messages = $this->aggregateMessages($form);

        if (!$suppress && $form->isArray()) {
            $messages = $this->wrap($messages, $form->getElementsBelongTo());
        }

        return $messages;
    }

    private function aggregateMessages(ZeroForm $form): array
    {
        $messages = [];

        /** @var Element $element */
        foreach ($form->getElements() as $name => $element) {
            $elementMessages = $element->getMessages();
            if ($elementMessages !== []) {
                $messages[$name] = $elementMessages;
            }
        }

        /** @var SubZeroForm $subForm */
        foreach ($form->getSubForms() as $key => $subForm) {
            $merge = $this->messagesForElement($subForm, null, true);
            if ($merge === []) {
                continue;
            }

            if (!$subForm->isArray()) {
                $merge = [(string) $key => $merge];
            } else {
                $merge = $this->wrap($merge, $subForm->getElementsBelongTo());
            }

            $messages = array_replace_recursive($messages, $merge);
        }

        return $messages;
    }

    private function path(?string $path): FieldPath
    {
        $path = (string) $path;

        return $this->paths[$path] ??= FieldPath::fromString($path);
    }

    private function read(array $value, ?string $path): mixed
    {
        return $this->path($path)->read($value);
    }

    private function remove(array $value, ?string $path, string $key): array
    {
        return $this->path($path)->remove($value, $key);
    }

    private function wrap(mixed $value, ?string $path): array
    {
        return $this->path($path)->wrap($value);
    }
}
