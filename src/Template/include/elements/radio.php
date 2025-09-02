<?php
/* radio.php */
use DalPraS\FormZero\Element\CheckboxMultiElement;
use DalPraS\FormZero\Element\RadioElement;
use DalPraS\FormZero\Element\RadioPopupElement;

return function($template, $element, string $name) {
    /** @var \DalPraS\SmartTemplate\TemplateEngine $template */
    /** @var \DalPraS\FormZero\Element\RadioElement|\DalPraS\FormZero\Element\RadioPopupElement|\DalPraS\FormZero\Element\CheckboxMultiElement $element */       

    $render = $this->renders[$name];
    $attribs = $element->getAttribs();
    // Compongo le multiopzioni
    $html = '';
    foreach ((array) $element->getMultiOptions() as $value => $text) {
        $text = $element->isTranslatorDisabled() ? $text : $template->getHelpers()->translator()->trans($text);
        $html .= $render['form']['html']['form-element-checkbox']([
            '{attributes}' => array_replace($attribs, [
                'class' => implode(' ',  [
                    $attribs['class'] ?? '',
                    $element->isValidated() ? ($element->hasErrors() ? 'is-invalid' : 'is-valid') : ''
                ]),
                'name'  => $attribs['name'] ?? $element->getFullyQualifiedName()
            ]),
            '{type}'       => match (get_class($element)) {
                CheckboxMultiElement::class 
                    => 'checkbox',
                RadioElement::class,
                RadioPopupElement::class,
                    => 'radio',
                default 
                    => ''
            },
            '{value}'   => $template->getHelpers()->escaper()->escapeHtmlAttr((string) $value),
            '{text}'    => $text, // $template->getHelpers()->escaper()->escapeHtml($text),
            '{checked}' => in_array((string) $value, (array) $element->getValue()) ? 'checked' : '',
            '{class}'   => $element->isInline() ? 'form-check-inline' : '',
        ]);
    }
    return $html;
};