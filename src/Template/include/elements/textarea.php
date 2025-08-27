<?php
/* textarea.php */
return function($template, $element, string $name) {
    /** @var \DalPraS\SmartTemplate\TemplateEngine $template */
    /** @var \DalPraS\FormZero\Element\TextareaElement $element */        
    $render = $this->renders[$name];
    $attribs = $element->getAttribs();
    return 
        $render['form']['html']['textarea']([
            '{attributes}' => array_replace($attribs, [
                'class' => implode(' ',  [
                    'form-control',
                    $attribs['class'] ?? '',
                    $element->isValidated() ? ($element->hasErrors() ? 'is-invalid' : 'is-valid') : ''
                ]),
                'id'    => $attribs['id'] ?? $attribs['name'] ?? $element->getFullyQualifiedName(),
                'name'  => $attribs['name'] ?? $element->getFullyQualifiedName(),
            ]),
            '{value}'   => $template->getHelpers()->escaper()->escapeHtmlAttr((string) $element->getValue()),
        ])
    ;
};