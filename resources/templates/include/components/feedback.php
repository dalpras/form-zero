<?php
/* feedback.php */

return function($element, string $name) {
    /** @var \DalPraS\FormZero\Element $element */

    $helpers = $this->getHelpers();    
    // Feedback
    if ($element->hasErrors()) {
        $render = $this->renders[$name];
        $html = $render->at('form.html.feedback')([
            '{content}' => function() use ($element, $helpers) {
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