<?php
/* hidden.php */
/** @var \DalPraS\SmartTemplate\TemplateEngine $this */

use DalPraS\SmartTemplate\Collection\RenderCollection;

return function(RenderCollection $render, $element) {
    /** @var \DalPraS\FormZero\Element\HiddenElement|\DalPraS\FormZero\Element\HashElement $element */
    $attribs = $element->getAttribs();

    $helpers = $this->getHelpers();

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