<?php
/* hidden.php */
return function($template, $element, string $name) {
    /** @var \DalPraS\SmartTemplate\TemplateEngine $template */
    /** @var \DalPraS\FormZero\Element\HiddenElement|\DalPraS\FormZero\Element\HashElement $element */    
    $render = $this->renders[$name];
    $attribs = $element->getAttribs();

    $html = $render['form']['html']['input']([
        '{attributes}' => array_replace($attribs, [
            'id'   => $attribs['id'] ?? $attribs['name'] ?? $element->getFullyQualifiedName(),
            'name' => $attribs['name'] ?? $element->getFullyQualifiedName(),
        ]),
        '{type}'    => 'hidden',
        '{value}'   => $template->getHelpers()->escaper()->escapeHtmlAttr((string) $element->getValue()),
    ]);

    return $html;
};