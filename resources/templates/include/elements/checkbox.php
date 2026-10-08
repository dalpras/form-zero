<?php
/* checkbox.php */
/** @var \DalPraS\SmartTemplate\TemplateEngine $this */

use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\FormZero\Element\CheckboxElement;
use DalPraS\SmartTemplate\Collection\RenderCollection;

return function(RenderCollection $render, CheckboxElement $element, AbstractDecorator $decorator) {
    $attributes = $element->getAttribs();

    $helpers = $this->getHelpers();

    // Field hidden with the Unchecked value
    // The hidden unchecked fallback is not a labelled/focusable control.
    // Do not duplicate the visible checkbox's ID or ARIA attributes.
    $hiddenAttributes = ['name' => $attributes['name'] ?? $element->getFullyQualifiedName()];
    if (isset($attributes['disabled'])) {
        $hiddenAttributes['disabled'] = $attributes['disabled'];
    }
    $html = $render->at('tag.input')([
        '{type}'       => 'hidden',
        '{value}'      => $helpers->escaper()->escapeHtml((string) $element->getUncheckedValue()),
        '{attributes}' => $hiddenAttributes,
    ]);

    $checkedValue = $element->getCheckedValue();
    $isChecked = $element->isChecked() || ((string) $element->getRenderValue() === $checkedValue);

    $checkboxAttributes = array_replace($attributes, [
        'class' => implode(' ',  [
            $attributes['class'] ?? '',
            $element->isValidated() ? ($element->hasErrors() ? 'is-invalid' : 'is-valid') : ''
        ]),
        'id'    => $attributes['id'] ?? $element->getId(),
        'name'  => $attributes['name'] ?? $element->getFullyQualifiedName(),
    ]);
    $checkboxAttributes = $element->applyValidationAccessibility($checkboxAttributes);

    // checkbox
    $html .= $render->at('form.html.form-element-checkbox')([
        '{attributes}' => $checkboxAttributes,
        '{type}'    => 'checkbox',
        '{value}'   => $helpers->escaper()->escapeHtml($checkedValue),
        '{content}' => '',
        '{checked}' => $isChecked ? 'checked' : '',
        '{class}' => 'form-check-inline',
    ]);
    return $html;
};