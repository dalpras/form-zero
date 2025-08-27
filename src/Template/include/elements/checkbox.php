<?php
/* checkbox.php */
return function($template, $element, string $name) {
    $render = $this->renders[$name];
    $attribs = $element->getAttribs();

    // Field hidden with the Unchecked value
    $html = $render['form']['html']['input']([
        '{attributes}' => array_replace($attribs, [
            'id'   => $attribs['id'] ?? $attribs['name'] ?? $element->getFullyQualifiedName(),
            'name' => $attribs['name'] ?? $element->getFullyQualifiedName()
        ]),
        '{type}'       => 'hidden',
        '{value}'      => $template->getHelpers()->escaper()->escapeHtmlAttr((string) $element->getUncheckedValue()),
    ]);
    unset($attribs['id']); // only one element can have a certain ID

    $checkedValue = $element->getCheckedValue();
    $isChecked = $element->isChecked() || ((string) $element->getValue() === $checkedValue);
    
    // checkbox
    $html .= $render['form']['html']['form-element-checkbox']([
        '{attributes}' => array_replace($attribs, [
            'class' => implode(' ',  [
                $attribs['class'] ?? '',
                $element->isValidated() ? ($element->hasErrors() ? 'is-invalid' : 'is-valid') : ''
            ]),
            'id'    => $attribs['id'] ?? $attribs['name'] ?? $element->getFullyQualifiedName(),
            'name'  => $attribs['name'] ?? $element->getFullyQualifiedName(),
        ]),
        '{type}'    => 'checkbox',
        '{value}'   => $template->getHelpers()->escaper()->escapeHtmlAttr($checkedValue),
        '{text}'    => '',
        '{checked}' => $isChecked ? 'checked' : '',
        '{class}' => 'form-check-inline',
    ]);
    return $html;
};