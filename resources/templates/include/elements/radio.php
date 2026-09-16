<?php
/* radio.php */
/** @var \DalPraS\SmartTemplate\TemplateEngine $this */

use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\FormZero\Element;
use DalPraS\FormZero\Element\CheckboxMultiElement;
use DalPraS\FormZero\Element\RadioElement;
use DalPraS\FormZero\Element\RadioPopupElement;
use DalPraS\SmartTemplate\Collection\RenderCollection;

return function(RenderCollection $render, Element $element, AbstractDecorator $decorator) {
    /** @var \DalPraS\FormZero\Element\RadioElement|\DalPraS\FormZero\Element\RadioPopupElement|\DalPraS\FormZero\Element\CheckboxMultiElement $element */

    $helpers = $this->getHelpers();
    $attributes = $element->getAttribs();
    // Compongo le multiopzioni
    $html = '';
    foreach ($element->getMultiChoices() as $label => $value) {
        $label = $element->isTranslatorDisabled()
            ? $label
            : $helpers->translator()->trans($label);

        $choiceAttributes = array_replace($attributes, [
            'class' => implode(' ',  [
                $attributes['class'] ?? '',
                $element->isValidated()
                    ? ($element->hasErrors() ? 'is-invalid' : 'is-valid')
                    : ''
            ]),
            'name'  => $attributes['name'] ?? $element->getFullyQualifiedName()
        ]);
        $choiceAttributes = $element->applyValidationAccessibility($choiceAttributes);

        $html .= $render->at('form.html.form-element-checkbox')([
            '{attributes}' => $choiceAttributes,
            '{type}'       => match (get_class($element)) {
                CheckboxMultiElement::class       => 'checkbox',
                RadioElement::class,
                RadioPopupElement::class          => 'radio',
                default                           => ''
            },
            '{value}'   => $helpers->escaper()->escapeHtml((string) $value),
            '{content}' => $label,
            '{checked}' => in_array((string) $value, (array) $element->getValue()) ? 'checked' : '',
            '{class}'   => $element->isInline() ? 'form-check-inline' : '',
        ]);
    }
    return $html;
};