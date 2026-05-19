<?php
/* description.php */

return function($element, string $name) {
    /** @var \DalPraS\FormZero\Element $element */
    $description = $element->getDescription();

    $helpers = $this->getHelpers();    
    if (strlen(trim($description)) > 0) {
        $render = $this->renders[$name];
        return $render->at('form.html.description')([
            '{text}' => $element->isTranslatorDisabled() ? $description: $helpers->translator()->trans($description)
        ]);
    }
    return '';
};