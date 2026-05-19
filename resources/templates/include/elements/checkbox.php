<?php
/* checkbox.php */
/** @var \DalPraS\SmartTemplate\TemplateEngine $this */

use DalPraS\SmartTemplate\Collection\RenderCollection;

return function(RenderCollection $render, $element) {
    $attributes = $element->getAttribs();

    $helpers = $this->getHelpers();

    // Field hidden with the Unchecked value
    $html = $render->at('form.html.input')([
        '{attributes}' => array_replace($attributes, [
            'id'   => $attributes['id'] ?? $attributes['name'] ?? $element->getFullyQualifiedName(),
            'name' => $attributes['name'] ?? $element->getFullyQualifiedName()
        ]),
        '{type}'       => 'hidden',
        '{value}'      => $helpers->escaper()->escapeHtmlAttr((string) $element->getUncheckedValue()),
    ]);
    unset($attributes['id']); // only one element can have a certain ID

    $checkedValue = $element->getCheckedValue();
    $isChecked = $element->isChecked() || ((string) $element->getValue() === $checkedValue);

    // checkbox
    $html .= $render->at('form.html.form-element-checkbox')([
        '{attributes}' => array_replace($attributes, [
            'class' => implode(' ',  [
                $attributes['class'] ?? '',
                $element->isValidated() ? ($element->hasErrors() ? 'is-invalid' : 'is-valid') : ''
            ]),
            'id'    => $attributes['id'] ?? $attributes['name'] ?? $element->getFullyQualifiedName(),
            'name'  => $attributes['name'] ?? $element->getFullyQualifiedName(),
        ]),
        '{type}'    => 'checkbox',
        '{value}'   => $helpers->escaper()->escapeHtmlAttr($checkedValue),
        '{content}' => '',
        '{checked}' => $isChecked ? 'checked' : '',
        '{class}' => 'form-check-inline',
    ]);
    return $html;
};