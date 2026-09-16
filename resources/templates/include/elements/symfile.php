<?php
/* symfile.php */
/** @var \DalPraS\SmartTemplate\TemplateEngine $this */

use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\FormZero\Element;
use DalPraS\SmartTemplate\Collection\RenderCollection;

return function(RenderCollection $render, Element $element, AbstractDecorator $decorator) {
    /** @var \DalPraS\FormZero\Element\SymfileElement|\DalPraS\FormZero\Element\SymfileMultiElement $element */

    $attributes = $element->getAttribs();
    $helpers = $this->getHelpers();

    $inputAttributes = array_replace($attributes, [
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
        ]);
    $inputAttributes = $element->applyValidationAccessibility($inputAttributes);

    $html = $render->at('tag.input')([
        '{type}' => 'file',
        '{value}' => $helpers->escaper()->escapeHtml((string) $element->getValue()),
        '{attributes}' => $inputAttributes,
    ]);
    return $html;
};