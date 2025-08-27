<?php
/* description.php */
return function($template, $element, string $name) {
    /** @var \DalPraS\SmartTemplate\TemplateEngine $template */
    /** @var \DalPraS\FormZero\Element $element */
    $description = $element->getDescription();
    if (strlen(trim($description)) > 0) {
        $render = $this->renders[$name];
        return $render['form']['html']['description']([
            '{text}' => $element->isTranslatorDisabled() ? $description: $this->getHelpers()->translator()->trans($description)
        ]);
    }
    return '';
};