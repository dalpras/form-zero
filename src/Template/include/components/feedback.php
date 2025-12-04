<?php
/* feedback.php */

use DalPraS\SmartTemplate\TemplateEngine;

return function(TemplateEngine $template, $element, string $name) {
    /** @var \DalPraS\SmartTemplate\TemplateEngine $template */
    /** @var \DalPraS\FormZero\Element $element */

    /** @var \DalPraS\SmartTemplate\TemplateEngine $template */
    $helpers = $template->getHelpers();    
    // Feedback
    if ($element->hasErrors()) {
        $render = $this->renders[$name];
        $html = $render['form']['html']['feedback']([
            '{text}' => function() use ($element, $helpers) {
                $feedbacks = [];
                // potrebbe essere recursive
                $iterator = new \RecursiveArrayIterator($element->getMessages());
                $recursiveIterator = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::LEAVES_ONLY);
                foreach ($recursiveIterator as $message) {
                    $feedbacks[] = $element->isTranslatorDisabled() ? $message : $helpers->translator()->trans($message);
                }
                return $helpers->escaper()->escapeHtml(implode('. ', $feedbacks));
            }
        ]);
        return $html;
    }
    return '';
};