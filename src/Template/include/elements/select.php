<?php
/* select.php */

use DalPraS\SmartTemplate\TemplateEngine;

return function(TemplateEngine $template, $element, string $name) {
    /** @var \DalPraS\SmartTemplate\TemplateEngine $template */
    /** @var \DalPraS\FormZero\Element\SelectElement|\DalPraS\FormZero\Element\SelectMultiElement $element */
    $render = $this->renders[$name];
    $attribs = $element->getAttribs();

    /** @var \DalPraS\SmartTemplate\TemplateEngine $template */
    $helpers = $template->getHelpers();

    $attribs['name'] ??= $element->getFullyQualifiedName();
    if (isset($attribs['multiple']) && substr($attribs['name'], -2) !== '[]') {
        $attribs['name'] .= '[]';
    }

    $html = $render['form']['html']['select']([
        '{attributes}' => array_replace($attribs, [
            'class' => implode(' ', [
                'form-select',
                $attribs['class'] ?? '',
                $element->isValidated() ? ($element->hasErrors() ? ' is-invalid' : ' is-valid') : '',
            ]),
            'id' => $attribs['id'] ?? $element->getFullyQualifiedName(),
        ]),
        '{options}' => function() use ($element, $render, $helpers) {
            // force $value to array so we can compare multiple values to multiple
            // options; also ensure it's a string for comparison purposes.
            $values = array_map(fn($value) => strval($value), (array) $element->getValue());
            $carry = '';
            foreach ($element->getMultiChoices() as $label => $value) {
                $text = $element->isTranslatorDisabled()
                    ? $label
                    : $helpers->translator()->trans($label);

                $carry .= $render['form']['html']['option']([
                    '{value}'    => $helpers->escaper()->escapeHtmlAttr((string) $value),
                    '{text}'     => $helpers->escaper()->escapeHtml($text),
                    '{selected}' => in_array((string) $value, $values) ? ' selected' : '',
                ]);
            }
            return $carry;
        },
    ]);
    return $html;
};