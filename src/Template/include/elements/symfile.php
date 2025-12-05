<?php
/* symfile.php */

use DalPraS\SmartTemplate\TemplateEngine;

return function(TemplateEngine $template, $element, string $name) {
    /** @var \DalPraS\SmartTemplate\TemplateEngine $template */
    /** @var \DalPraS\FormZero\Element\SymfileElement|\DalPraS\FormZero\Element\SymfileMultiElement $element */
    $render = $this->renders[$name];
    $attribs = $element->getAttribs();

    /** @var \DalPraS\SmartTemplate\TemplateEngine $template */
    $helpers = $template->getHelpers();

    $html = $render['form']['html']['input']([
        '{attributes}' => array_replace($attribs, [
            'class' => implode(' ',  [
                'form-control',
                $attribs['class'] ?? '',
                $element->isValidated() 
                    ? ($element->hasErrors() 
                        ? 'is-invalid' 
                        : 'is-valid') 
                    : ''
            ]),
            'id'    => $attribs['id'] ?? $attribs['name'] ?? $element->getFullyQualifiedName(),
            'name'  => $attribs['name'] ?? $element->getFullyQualifiedName(),
        ]),
        '{type}' => 'file',
        '{value}' => $helpers->escaper()->escapeHtmlAttr((string) $element->getValue()),
    ]);
    return $html;
};