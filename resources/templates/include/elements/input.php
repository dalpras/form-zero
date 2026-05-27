<?php
/* input.php */
/** @var \DalPraS\SmartTemplate\TemplateEngine $this */

use DalPraS\FormZero\Element\EmailElement;
use DalPraS\FormZero\Element\PasswordElement;
use DalPraS\FormZero\Element\SearchElement;
use DalPraS\FormZero\Element\TextElement;
use DalPraS\SmartTemplate\Collection\RenderCollection;

return function(RenderCollection $render, $element) {
    /** @var TextElement|EmailElement|SearchElement|PasswordElement $element */
    $attributes = $element->getAttribs();

    $helpers = $this->getHelpers();

    $html = $render->at('form.html.input')([
        '{attributes}' => array_replace($attributes, [
            'class' => implode(' ',  [
                'form-control',
                $attributes['class'] ?? '',
                $element->isValidated() ? ($element->hasErrors() ? 'is-invalid' : 'is-valid') : ''
            ]),
            'id'    => $attributes['id'] ?? $attributes['name'] ?? $element->getFullyQualifiedName(),
            'name'  => $attributes['name'] ?? $element->getFullyQualifiedName(),
        ]),
        '{type}' => match (get_class($element)) {
            TextElement::class
                => 'text',
            EmailElement::class
                => 'email',
            SearchElement::class
                => 'search',
            PasswordElement::class
                => 'password',
            default
                => 'text'
        },
        '{value}' => $helpers->escaper()->escapeHtml((string) $element->getValue()),
    ]);
    return $html;
};