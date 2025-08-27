<?php
/* label.php */
return function($template, $element, string $name) {
    /** @var \DalPraS\SmartTemplate\TemplateEngine $template */
    /** @var \DalPraS\FormZero\Element $element */    
    $render = $this->renders[$name];
    return $render['form']['html']['label']([
        '{class}'    => 'col-form-label ' . ($options['{label-class}'] ?? ''),
        '{for}'      => $element->getId(),
        '{required}' => $element->isRequired() ? 'required' : '',
        '{text}'     => $element->isTranslatorDisabled() ? $element->getLabel() : $this->getHelpers()->translator()->trans($element->getLabel())
    ]);
};