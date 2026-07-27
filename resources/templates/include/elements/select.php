<?php
/* select.php */
/** @var \DalPraS\SmartTemplate\TemplateEngine $this */

use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\FormZero\Element;
use DalPraS\SmartTemplate\Collection\RenderCollection;

return function(RenderCollection $render, Element $element, AbstractDecorator $decorator) {
    /** @var \DalPraS\FormZero\Element\SelectElement|\DalPraS\FormZero\Element\SelectMultiElement $element */

    $attributes = $element->getAttribs();
    $helpers = $this->getHelpers();

    $attributes['name'] ??= $element->getFullyQualifiedName();
    if (isset($attributes['multiple']) && substr($attributes['name'], -2) !== '[]') {
        $attributes['name'] .= '[]';
    }

    $html = $render->at('tag.select')([
        '{attributes}' => array_replace($attributes, [
            'class' => implode(' ', [
                'form-select',
                $attributes['class'] ?? '',
                $element->isValidated() ? ($element->hasErrors() ? ' is-invalid' : ' is-valid') : '',
            ]),
            'id' => $attributes['id'] ?? $element->getFullyQualifiedName(),
        ]),
        '{content}' => function() use ($element, $render, $helpers) {
            // force $value to array so we can compare multiple values to multiple
            // options; also ensure it's a string for comparison purposes.
            $values = array_map(fn($value) => strval($value), (array) $element->getValue());
            $carry = '';
            foreach ($element->getMultiChoices() as $label => $value) {
                $text = $element->isTranslatorDisabled()
                    ? $label
                    : $helpers->translator()->trans($label);

                $carry .= $render->at('tag.option')([
                    '{attributes}' => array_merge($element->getChoiceAttributes($label), in_array((string) $value, $values) ? ['selected' => ''] : []),
                    '{value}'      => $helpers->escaper()->escapeHtml((string) $value),
                    '{content}'       => $helpers->escaper()->escapeHtml($text),
                ]);
            }
            return $carry;
        },
    ]);
    return $html;
};