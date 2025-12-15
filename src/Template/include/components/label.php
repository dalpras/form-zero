<?php
/* label.php */

use DalPraS\SmartTemplate\TemplateEngine;

return function(TemplateEngine $template, $element, string $name) {
    /** @var \DalPraS\SmartTemplate\TemplateEngine $template */
    /** @var \DalPraS\FormZero\Element $element */    
    $render = $this->renders[$name];

    /** @var \DalPraS\SmartTemplate\TemplateEngine $template */
    $helpers = $template->getHelpers();    

    return $render['form']['html']['label']([
        '{class}'    => 'col-form-label',
        '{for}'      => $element->getId(),
        '{required}' => $element->isRequired() ? 'required' : '',
        '{text}'     => $element->isTranslatorDisabled() ? $element->getLabel() : $helpers->translator()->trans($element->getLabel())
    ]);
};