<?php
/* input.php */
/** @var \DalPraS\SmartTemplate\TemplateEngine $this */

use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\FormZero\Element;
use DalPraS\FormZero\Element\EmailElement;
use DalPraS\FormZero\Element\PasswordElement;
use DalPraS\FormZero\Element\SearchElement;
use DalPraS\FormZero\Element\TextElement;
use DalPraS\SmartTemplate\Collection\RenderCollection;

return function(RenderCollection $render, Element $element, AbstractDecorator $decorator) {
    /** @var TextElement|EmailElement|SearchElement|PasswordElement $element */
    $attributes = $element->getAttribs();

    $helpers = $this->getHelpers();

    $isSearch = $element instanceof SearchElement;
    $validationClass = '';
    if ($element->isValidated()) {
        $validationClass = $element->hasErrors()
            ? 'is-invalid'
            : ($isSearch ? '' : 'is-valid');
    }

    $inputAttributes = array_replace($attributes, [
        'class' => implode(' ',  [
            'form-control',
            $attributes['class'] ?? '',
            $decorator->getOption('attributes')['class'] ?? '',
            $validationClass
        ]),
        'id'    => $attributes['id'] ?? $attributes['name'] ?? $element->getFullyQualifiedName(),
        'name'  => $attributes['name'] ?? $element->getFullyQualifiedName(),
    ]);

    if ($isSearch) {
        $inputAttributes['data-search-clear-input'] = true;
    }

    $inputAttributes = $element->applyValidationAccessibility($inputAttributes);

    $html = $render->at('tag.input')([
        '{type}' => match (get_class($element)) {
            TextElement::class => 'text',
            EmailElement::class => 'email',
            SearchElement::class => 'search',
            PasswordElement::class => 'password',
            default => 'text'
        },
        '{value}' => $helpers->escaper()->escapeHtml((string) $element->getValue()),
        '{attributes}' => $inputAttributes,
    ]);

    if (!$isSearch) {
        return $html;
    }

    $html .= $render->at('tag.button')([
        '{attributes}' => [
            'type' => 'button',
            'class' => 'search-clear-button',
            'data-search-clear-button' => true,
            'aria-label' => $helpers->trans('Rimuovi testo digitato'),
            'hidden' => true,
        ],
        '{content}' => '<span aria-hidden="true">&times;</span>',
    ]);

    return '<span class="search-clear-control" data-search-clear>' . $html . '</span>';
};
