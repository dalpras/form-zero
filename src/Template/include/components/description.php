<?php
/* description.php */

use DalPraS\SmartTemplate\TemplateEngine;

return function(TemplateEngine $template, $element, string $name) {
    /** @var \DalPraS\SmartTemplate\TemplateEngine $template */
    /** @var \DalPraS\FormZero\Element $element */
    $description = $element->getDescription();

    /** @var \DalPraS\SmartTemplate\TemplateEngine $template */
    $helpers = $template->getHelpers();    
    if (strlen(trim($description)) > 0) {
        $render = $this->renders[$name];
        return $render['form']['html']['description']([
            '{text}' => $element->isTranslatorDisabled() ? $description: $helpers->translator()->translate($description)
        ]);
    }
    return '';
};