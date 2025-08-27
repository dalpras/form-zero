<?php
/* symfile.php */

return function($template, $element, string $name) {
    /** @var \DalPraS\SmartTemplate\TemplateEngine $template */
    /** @var \DalPraS\FormZero\Element\SymfileElement|\DalPraS\FormZero\Element\SymfileMultiElement $element */
    $render = $this->renders[$name];
    $attribs = $element->getAttribs();

    $html = $render['form']['html']['input']([
        '{attributes}' => array_replace($attribs, [
            'class' => implode(' ',  [
                'form-control',
                $attribs['class'] ?? '',
                $element->isValidated() ? ($element->hasErrors() ? 'is-invalid' : 'is-valid') : ''
            ]),
            'id'    => $attribs['id'] ?? $attribs['name'] ?? $element->getFullyQualifiedName(),
            'name'  => $attribs['name'] ?? $element->getFullyQualifiedName(),
        ]),
        '{type}' => 'file',
        '{value}' => $template->getHelpers()->escaper()->escapeHtmlAttr((string) $element->getValue()),
    ]);
    return $html;
};