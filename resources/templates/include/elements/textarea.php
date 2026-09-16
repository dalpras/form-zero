<?php
/* textarea.php */
/** @var \DalPraS\SmartTemplate\TemplateEngine $this */

use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\FormZero\Element;
use DalPraS\SmartTemplate\Collection\RenderCollection;

return function(RenderCollection $render, Element $element, AbstractDecorator $decorator) {
    /** @var \DalPraS\FormZero\Element\TextareaElement $element */

    $attributes = $element->getAttribs();
    $helpers = $this->getHelpers();

    $textareaAttributes = array_replace($attributes, [
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
    $textareaAttributes = $element->applyValidationAccessibility($textareaAttributes);

    $html = $render->at('tag.textarea')([
        '{attributes}' => $textareaAttributes,
        '{content}' => $helpers->escaper()->escapeHtml((string) $element->getValue()),
    ]);
    return $html;
};