<?php
/* date-picker.php */

use DalPraS\SmartTemplate\TemplateEngine;

return function(TemplateEngine $template, $element, string $name) {
    /** @var \DalPraS\SmartTemplate\TemplateEngine $template */
    /** @var \DalPraS\FormZero\Element\DatePickerElement $element */        
    $render = $this->renders[$name];
    $attribs = $element->getAttribs();

    /** @var \DalPraS\SmartTemplate\TemplateEngine $template */
    $helpers = $template->getHelpers();    

    $html = $render['form']['html']['datepicker']([
        '{attributes}' => array_replace($attribs, [
            'class' => implode(' ',  [
                'form-control',
                $attribs['class'] ?? '',
                $element->isValidated() ? ($element->hasErrors() ? 'is-invalid' : 'is-valid') : ''
            ]),
            'id'    => $attribs['id'] ?? $attribs['name'] ?? $element->getFullyQualifiedName(),
            'name' => $attribs['name'] ?? $element->getFullyQualifiedName()
        ]),
        '{type}'    => 'text',
        '{value}'   => $helpers->escaper()->escapeHtmlAttr((string) $element->getValue()),
    ]);
    return $html;
};