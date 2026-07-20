<?php
/* label.php */

return function($element, string $name) {
    /** @var \DalPraS\FormZero\Element $element */    
    $render = $this->renders[$name];

    $helpers = $this->getHelpers();    

    return $render->at('tag.label')([
        '{attributes}' => [
            'class' => 'col-form-label ' . ($element->isRequired() ? 'required' : ''),
            'for'   => $element->getId(),
        ],
        '{content}' => $element->isTranslatorDisabled() 
            ? $element->getLabel() 
            : $helpers->translator()->trans($element->getLabel())
    ]);
};