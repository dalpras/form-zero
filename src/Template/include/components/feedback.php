<?php
/* feedback.php */
return function($template, $element, string $name) {
    /** @var \DalPraS\SmartTemplate\TemplateEngine $template */
    /** @var \DalPraS\FormZero\Element $element */
    // Feedback
    if ($element->hasErrors()) {
        $render = $this->renders[$name];
        $html = $render['form']['html']['feedback']([
            '{text}' => function() use ($element) {
                $feedbacks = [];
                // potrebbe essere recursive
                $iterator = new \RecursiveArrayIterator($element->getMessages());
                $recursiveIterator = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::LEAVES_ONLY);
                foreach ($recursiveIterator as $message) {
                    $feedbacks[] = $element->isTranslatorDisabled() ? $message : $this->getHelpers()->translator()->trans($message);
                }
                return $this->getHelpers()->escaper()->escapeHtml(implode('. ', $feedbacks));
            }
        ]);
        return $html;
    }
    return '';
};