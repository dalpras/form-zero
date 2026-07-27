<?php
/* submit.php */
/** @var \DalPraS\SmartTemplate\TemplateEngine $this */

use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\FormZero\Element;
use DalPraS\SmartTemplate\Collection\RenderCollection;

return function(RenderCollection $render, Element $element, AbstractDecorator $decorator) {
    /** @var \DalPraS\FormZero\Element\SubmitElement $element */
    
    $attributes = $element->getAttribs();
    $helpers = $this->getHelpers();    

    $attributes = array_replace($attributes, [
        'class' => implode(' ',  [
            $attributes['class'] ?? '',
            $element->isValidated() ? ($element->hasErrors() ? 'is-invalid' : 'is-valid') : ''
        ]),
        'type'  => 'submit',
        'value' => $helpers->escaper()->escapeHtml((string) $element->getValue()),
        'id'    => $attributes['id'] ?? $attributes['name'] ?? $element->getFullyQualifiedName(),
        'name'  => $attributes['name'] ?? $element->getFullyQualifiedName(),
    ]);

    $html = $render->at('tag.button')([
        '{attributes}' => $attributes,
        '{content}' => $element->getText()
    ]);
    return $html;
};