<?php
/* select.php */
return function($template, $element, string $name) {
    /** @var \DalPraS\SmartTemplate\TemplateEngine $template */
    /** @var \DalPraS\FormZero\Element\SelectElement|\DalPraS\FormZero\Element\SelectMultiElement $element */       
    $render = $this->renders[$name];
    $attribs = $element->getAttribs();

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
        '{options}' => function() use ($element, $render, $template) {
            // force $value to array so we can compare multiple values to multiple
            // options; also ensure it's a string for comparison purposes.
            $values = array_map(fn($value) => strval($value), (array) $element->getValue());
            $carry = '';
            foreach ($element->getMultiOptions() as $value => $text) {
                $text = $element->isTranslatorDisabled() ? $text : $this->getHelpers()->translator()->trans($text);
                $carry .= $render['form']['html']['option']([
                    '{value}'    => $template->getHelpers()->escaper()->escapeHtmlAttr($value),
                    '{text}'     => $template->getHelpers()->escaper()->escapeHtml($text),
                    '{selected}' => in_array((string) $value, $values) ? ' selected' : '',
                ]);
            }
            return $carry;
        },
    ]);
    return $html;
};