<?php
/* symfile.php */
/** @var \DalPraS\SmartTemplate\TemplateEngine $this */

use DalPraS\SmartTemplate\Collection\RenderCollection;

return function(RenderCollection $render, $element) {
    /** @var \DalPraS\FormZero\Element\SymfileElement|\DalPraS\FormZero\Element\SymfileMultiElement $element */

    $attributes = $element->getAttribs();
    $helpers = $this->getHelpers();

    $html = $render->at('form.html.input')([
        '{attributes}' => array_replace($attributes, [
            'class' => implode(' ',  [
                'form-control',
                $attributes['class'] ?? '',
                $element->isValidated() 
                    ? ($element->hasErrors() 
                        ? 'is-invalid' 
                        : 'is-valid') 
                    : ''
            ]),
            'id'    => $attributes['id'] ?? $attributes['name'] ?? $element->getFullyQualifiedName(),
            'name'  => $attributes['name'] ?? $element->getFullyQualifiedName(),
        ]),
        '{type}' => 'file',
        '{value}' => $helpers->escaper()->escapeHtml((string) $element->getValue()),
    ]);
    return $html;
};