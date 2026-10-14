<?php
/* radio.php */
/** @var \DalPraS\SmartTemplate\TemplateEngine $this */

use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\FormZero\Element;
use DalPraS\FormZero\Element\CheckboxMultiElement;
use DalPraS\FormZero\Element\RadioElement;
use DalPraS\FormZero\Element\RadioPopupElement;
use DalPraS\SmartTemplate\Collection\RenderCollection;
use DalPraS\SmartTemplate\HtmlAttributes;

return function(RenderCollection $render, Element $element, AbstractDecorator $decorator) {
    /** @var \DalPraS\FormZero\Element\RadioElement|\DalPraS\FormZero\Element\RadioPopupElement|\DalPraS\FormZero\Element\CheckboxMultiElement $element */

    $helpers = $this->getHelpers();
    $attributes = $element->getAttribs();
    // Compongo le multiopzioni
    $html = '';
    $baseId = (string) ($attributes['id'] ?? $element->getId());
    $htmlChoiceLabels = $element instanceof RadioElement && $element->allowsHtmlChoiceLabels();
    $choiceIndex = 0;
    foreach ($element->getMultiChoices() as $label => $value) {
        // IDs must be unique within a radio/checkbox group, and must match
        // the labels so clicking an icon actually selects the input.
        $choiceId = $baseId . ($choiceIndex === 0 ? '' : '-' . $choiceIndex);
        ++$choiceIndex;
        // Trusted HTML is already rendered markup, not a translation key.
        $label = $htmlChoiceLabels || $element->isTranslatorDisabled()
            ? $label
            : $helpers->translator()->trans($label);

        $choiceAttributes = array_replace($attributes, [
            'class' => implode(' ',  [
                $attributes['class'] ?? '',
                $element->isValidated()
                    ? ($element->hasErrors() ? 'is-invalid' : 'is-valid')
                    : ''
            ]),
            'id'    => $choiceId,
            'name'  => $attributes['name'] ?? $element->getFullyQualifiedName()
        ]);
        $choiceAttributes = $element->applyValidationAccessibility($choiceAttributes);

        $html .= $render->at('form.html.form-element-checkbox')([
            '{attributes}|join' => $choiceAttributes,
            '{type}'       => match (get_class($element)) {
                CheckboxMultiElement::class       => 'checkbox',
                RadioElement::class,
                RadioPopupElement::class          => 'radio',
                default                           => ''
            },
            '{value}'   => $helpers->escaper()->escapeHtml((string) $value),
            // Unescaped HTML is an explicit RadioElement opt-in, never
            // inferred from the presence of tags in the option label.
            '{content}' => $htmlChoiceLabels
                ? (string) $label
                : $helpers->escaper()->escapeHtml((string) $label),
            '{labelFor}' => $helpers->escaper()->escapeHtml(HtmlAttributes::normalizeId($choiceId)),
            '{checked}' => in_array((string) $value, (array) $element->getRenderValue()) ? 'checked' : '',
            '{class}'   => $element->isInline() ? 'form-check-inline' : '',
        ]);
    }
    return $html;
};