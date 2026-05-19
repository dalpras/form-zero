<?php
/* date-picker.php */
/** @var \DalPraS\SmartTemplate\TemplateEngine $this */

use DalPraS\SmartTemplate\Collection\RenderCollection;

return function(RenderCollection $render, $element) {
    /** @var \DalPraS\FormZero\Element\DatePickerElement $element */
    $attributes = $element->getAttribs();

    $helpers = $this->getHelpers();

    $html = $render->at('form.html.datepicker')([
        '{attributes}' => array_replace($attributes, [
            'class' => implode(' ',  [
                'form-control',
                $attributes['class'] ?? '',
                $element->isValidated() ? ($element->hasErrors() ? 'is-invalid' : 'is-valid') : ''
            ]),
            'id'    => $attributes['id'] ?? $attributes['name'] ?? $element->getFullyQualifiedName(),
            'name' => $attributes['name'] ?? $element->getFullyQualifiedName()
        ]),
        '{type}'    => 'text',
        '{value}'   => $helpers->escaper()->escapeHtmlAttr((string) $element->getValue()),
    ]);
    return $html;
};