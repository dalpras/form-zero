<?php
/* submit.php */
return function($template, $element, string $name) {
    /** @var \DalPraS\SmartTemplate\TemplateEngine $template */
    /** @var \DalPraS\FormZero\Element\SubmitElement $element */          
    $render = $this->renders[$name];
    $attribs = $element->getAttribs();
    $attributes = array_replace($attribs, [
        'class' => implode(' ',  [
            $attribs['class'] ?? '',
            $element->isValidated() ? ($element->hasErrors() ? 'is-invalid' : 'is-valid') : ''
        ]),
        'type'  => 'submit',
        'value' => $element->getValue() ?? ($element->getOptions()['value']) ?? null,
        'id'    => $attribs['id'] ?? $attribs['name'] ?? $element->getFullyQualifiedName(),
        'name'  => $attribs['name'] ?? $element->getFullyQualifiedName(),
    ]);
    
    $html = $render['form']['html']['button']([
        '{attributes}' => $attributes,
        '{text}' => $element->getLabel()
    ]);
    return $html;
};