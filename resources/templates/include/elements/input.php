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
    $attribs = $element->getAttribs();

    $helpers = $this->getHelpers();

    $html = $render->at('form.html.input')([
        '{attributes}' => array_replace($attribs, [
            'class' => implode(' ',  [
                'form-control',
                $attribs['class'] ?? '',
                $element->isValidated() ? ($element->hasErrors() ? 'is-invalid' : 'is-valid') : ''
            ]),
            'id'    => $attribs['id'] ?? $attribs['name'] ?? $element->getFullyQualifiedName(),
            'name'  => $attribs['name'] ?? $element->getFullyQualifiedName(),
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
        '{value}' => $helpers->escaper()->escapeHtmlAttr((string) $element->getValue()),
    ]);
    return $html;
};