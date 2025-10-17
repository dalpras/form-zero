<?php
/* hidden.php */

use DalPraS\SmartTemplate\TemplateEngine;

return function(TemplateEngine $template, $element, string $name) {
    /** @var \DalPraS\SmartTemplate\TemplateEngine $template */
    /** @var \DalPraS\FormZero\Element\HiddenElement|\DalPraS\FormZero\Element\HashElement $element */    
    $render = $this->renders[$name];
    $attribs = $element->getAttribs();

    /** @var \DalPraS\SmartTemplate\TemplateEngine $template */
    $helpers = $template->getHelpers();   

    $html = $render['form']['html']['input']([
        '{attributes}' => array_replace($attribs, [
            'id'   => $attribs['id'] ?? $attribs['name'] ?? $element->getFullyQualifiedName(),
            'name' => $attribs['name'] ?? $element->getFullyQualifiedName(),
        ]),
        '{type}'    => 'hidden',
        '{value}'   => $helpers->escaper()->escapeHtmlAttr((string) $element->getValue()),
    ]);

    return $html;
};