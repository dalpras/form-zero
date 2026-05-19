<?php
/* radio.php */
/** @var \DalPraS\SmartTemplate\TemplateEngine $this */

use DalPraS\FormZero\Element\CheckboxMultiElement;
use DalPraS\FormZero\Element\RadioElement;
use DalPraS\FormZero\Element\RadioPopupElement;
use DalPraS\SmartTemplate\Collection\RenderCollection;

return function(RenderCollection $render, $element) {
    /** @var \DalPraS\FormZero\Element\RadioElement|\DalPraS\FormZero\Element\RadioPopupElement|\DalPraS\FormZero\Element\CheckboxMultiElement $element */

    $helpers = $this->getHelpers();
    $attribs = $element->getAttribs();
    // Compongo le multiopzioni
    $html = '';
    foreach ($element->getMultiChoices() as $label => $value) {
        $label = $element->isTranslatorDisabled()
            ? $label
            : $helpers->translator()->trans($label);

        $html .= $render->at('form.html.form-element-checkbox')([
            '{attributes}' => array_replace($attribs, [
                'class' => implode(' ',  [
                    $attribs['class'] ?? '',
                    $element->isValidated()
                        ? ($element->hasErrors() ? 'is-invalid' : 'is-valid')
                        : ''
                ]),
                'name'  => $attribs['name'] ?? $element->getFullyQualifiedName()
            ]),
            '{type}'       => match (get_class($element)) {
                CheckboxMultiElement::class       => 'checkbox',
                RadioElement::class,
                RadioPopupElement::class          => 'radio',
                default                           => ''
            },
            '{value}'   => $helpers->escaper()->escapeHtmlAttr((string) $value),
            '{text}'    => $label,
            '{checked}' => in_array((string) $value, (array) $element->getValue()) ? 'checked' : '',
            '{class}'   => $element->isInline() ? 'form-check-inline' : '',
        ]);
    }
    return $html;
};