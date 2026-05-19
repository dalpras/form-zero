<?php
/* textarea.php */
/** @var \DalPraS\SmartTemplate\TemplateEngine $this */

use DalPraS\SmartTemplate\Collection\RenderCollection;

return function(RenderCollection $render, $element) {
    /** @var \DalPraS\FormZero\Element\TextareaElement $element */

    $attribs = $element->getAttribs();
    $helpers = $this->getHelpers();

    $html = $render->at('form.html.textarea')([
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
        '{value}'   => $helpers->escaper()->escapeHtmlAttr((string) $element->getValue()),
    ]);
    return $html;
};