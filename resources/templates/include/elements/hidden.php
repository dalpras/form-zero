<?php
/* hidden.php */
/** @var \DalPraS\SmartTemplate\TemplateEngine $this */

use DalPraS\SmartTemplate\Collection\RenderCollection;

return function(RenderCollection $render, $element) {
    /** @var \DalPraS\FormZero\Element\HiddenElement|\DalPraS\FormZero\Element\HashElement $element */
    $attributes = $element->getAttribs();

    $helpers = $this->getHelpers();

    $html = $render->at('tag.input')([
        '{type}'    => 'hidden',
        '{value}'   => $helpers->escaper()->escapeHtml((string) $element->getValue()),
        '{attributes}' => array_replace($attributes, [
            'id'   => $attributes['id'] ?? $attributes['name'] ?? $element->getFullyQualifiedName(),
            'name' => $attributes['name'] ?? $element->getFullyQualifiedName(),
        ]),
    ]);

    return $html;
};