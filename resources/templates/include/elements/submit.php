<?php
/* submit.php */
/** @var \DalPraS\SmartTemplate\TemplateEngine $this */

use DalPraS\SmartTemplate\Collection\RenderCollection;

return function(RenderCollection $render, $element) {
    /** @var \DalPraS\FormZero\Element\SubmitElement $element */
    
    $attribs = $element->getAttribs();
    $helpers = $this->getHelpers();    

    $attributes = array_replace($attribs, [
        'class' => implode(' ',  [
            $attribs['class'] ?? '',
            $element->isValidated() ? ($element->hasErrors() ? 'is-invalid' : 'is-valid') : ''
        ]),
        'type'  => 'submit',
        // 'value' => $element->getValue(), // ?? ($element->getOptions()['value']) ?? null,
        'value' => $helpers->escaper()->escapeHtmlAttr((string) $element->getValue()),
        'id'    => $attribs['id'] ?? $attribs['name'] ?? $element->getFullyQualifiedName(),
        'name'  => $attribs['name'] ?? $element->getFullyQualifiedName(),
    ]);

    $html = $render->at('form.html.button')([
        '{attributes}' => $attributes,
        '{text}' => $element->getText()
    ]);
    return $html;
};