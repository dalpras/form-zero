<?php declare(strict_types=1);

namespace DalPraS\FormZero;

/**
 * Internal data traversal for ZeroForm.
 *
 * Keeps default assignment and value extraction separate from the public form
 * facade while preserving the existing array/belongsTo semantics.
 *
 * @internal
 */
final class FormDataMapper
{
    /** @var array<string, FieldPath> */
    private array $paths = [];

    public function setDefaults(ZeroForm $form, array $defaults): void
    {
        $elementsBelongTo = null;

        if ($form->isArray()) {
            $elementsBelongTo = $form->getElementsBelongTo();
            $defaults = $this->read($defaults, $elementsBelongTo);
        }

        /** @var Element $element */
        foreach ($form->getElements() as $name => $element) {
            $check = $defaults;
            if (($belongsTo = $element->getBelongsTo()) !== $elementsBelongTo) {
                $check = $this->read($defaults, $belongsTo);
            }

            if (array_key_exists($name, (array) $check)) {
                $form->setDefault($name, $check[$name]);
                $defaults = $this->remove($defaults, $belongsTo, $name);
            }
        }

        /** @var SubZeroForm $subForm */
        foreach ($form->getSubForms() as $name => $subForm) {
            if (!$subForm->isArray() && array_key_exists($name, $defaults)) {
                $subForm->setDefaults($defaults[$name]);
            } else {
                $subForm->setDefaults($defaults);
            }
        }
    }

    public function getValues(ZeroForm $form, bool $suppressArrayNotation = false): array
    {
        $values = [];
        $elementsBelongTo = $form->isArray() ? $form->getElementsBelongTo() : null;

        /** @var Element $element */
        foreach ($form->getElements() as $key => $element) {
            if ($element->getIgnore()) {
                continue;
            }

            $values = array_replace_recursive(
                $values,
                $this->elementPath(
                    (string) $key,
                    $element->getBelongsTo(),
                    $elementsBelongTo
                )->wrap($element->getValue())
            );
        }

        /** @var SubZeroForm $subForm */
        foreach ($form->getSubForms() as $key => $subForm) {
            if (!$subForm->isArray()) {
                $merge = [(string) $key => $subForm->getValues()];
            } else {
                $merge = $this->wrap(
                    $subForm->getValues(true),
                    $subForm->getElementsBelongTo()
                );
            }

            $values = array_replace_recursive($values, $merge);
        }

        if (!$suppressArrayNotation && $form->isArray()) {
            $values = $this->wrap($values, $form->getElementsBelongTo());
        }

        return $values;
    }

    public function getValidValues(ZeroForm $form, array $data, bool $suppressArrayNotation = false): array
    {
        $values = [];
        $elementsBelongTo = null;

        if ($form->isArray()) {
            $elementsBelongTo = $form->getElementsBelongTo();
            $data = $this->read($data, $elementsBelongTo);
        }

        $context = $data;

        /** @var Element $element */
        foreach ($form->getElements() as $key => $element) {
            if ($element->getIgnore()) {
                continue;
            }

            $check = $data;
            $belongsTo = $element->getBelongsTo();
            if ($belongsTo !== $elementsBelongTo) {
                $check = $this->read($data, $belongsTo);
            }

            if (isset($check[$key])) {
                if ($element->isValid($check[$key], $context)) {
                    $values = array_replace_recursive(
                        $values,
                        $this->elementPath(
                            (string) $key,
                            $belongsTo,
                            $elementsBelongTo
                        )->wrap($element->getValue())
                    );
                }

                $data = $this->remove($data, $belongsTo, (string) $key);
            }
        }

        /** @var SubZeroForm $subForm */
        foreach ($form->getSubForms() as $key => $subForm) {
            if (isset($data[$key]) && !$subForm->isArray()) {
                $tmp = $subForm->getValidValues($data[$key]);
                $merge = $tmp === [] ? [] : [(string) $key => $tmp];
            } else {
                $tmp = $subForm->getValidValues($data, true);
                $merge = $tmp === []
                    ? []
                    : $this->wrap($tmp, $subForm->getElementsBelongTo());
            }

            $values = array_replace_recursive($values, $merge);
        }

        if (!$suppressArrayNotation && $form->isArray() && $values !== []) {
            $values = $this->wrap($values, $form->getElementsBelongTo());
        }

        return $values;
    }

    private function elementPath(string $key, ?string $belongsTo, ?string $elementsBelongTo): FieldPath
    {
        if ($belongsTo !== $elementsBelongTo && $belongsTo !== '') {
            return $this->path($belongsTo . '[' . $key . ']');
        }

        return $this->path($key);
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
