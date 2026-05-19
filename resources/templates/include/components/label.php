<?php
/* label.php */

return function($element, string $name) {
    /** @var \DalPraS\FormZero\Element $element */    
    $render = $this->renders[$name];

    $helpers = $this->getHelpers();    

    return $render->at('form.html.label')([
        '{class}'    => 'col-form-label',
        '{for}'      => $element->getId(),
        '{required}' => $element->isRequired() ? 'required' : '',
        '{content}'     => $element->isTranslatorDisabled() ? $element->getLabel() : $helpers->translator()->trans($element->getLabel())
    ]);
};