<?php declare(strict_types=1);

namespace DalPraS\FormZero\Decorator;

use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\SmartTemplate\Collection\RenderCollection;

/**
 * Fieldset
 *
 * Questo decoratore aggiunge la parte html fieldset ai campi della form.
 * Utilizza anche l'helper fieldset per renderizzare il tutto.
 *
 * Any options passed will be used as HTML attributes of the fieldset tag.
 */
class FieldsetDecorator extends AbstractDecorator
{
    /**
     * Fieldset legend
     */
    private string $legend = '';

    /**
     * Get options
     * Merges in element attributes as well.
     */
    public function getOptions(): array
    {
        $options = parent::getOptions();
        if (null !== ($element = $this->getElement())) {
            $attributes = $element->getAttribs();
            $options = array_merge($attributes, $options);
            $this->setOptions($options);
        }
        return $options;
    }

    /**
     * Set legend
     */
    public function setLegend(string $value): static
    {
        $this->legend = (string) $value;
        return $this;
    }

    /**
     * Get legend
     */
    public function getLegend(): string
    {
        $legend = $this->legend;
        if ((null === $legend) && (null !== ($element = $this->getElement()))) {
            if (method_exists($element, 'getLegend')) {
                $legend = $element->getLegend();
                $this->setLegend($legend);
            }
        }
        if ((null === $legend) && (null !== ($legend = $this->getOption('legend')))) {
            $this->setLegend($legend);
            $this->removeOption('legend');
        }

        return $legend;
    }

    /**
     * Render a fieldset
     */
    public function render(string $content = ''): string
    {
        /** @var \DalPraS\FormZero\Element $element */
        $element = $this->getElement();

        $attributes = $this->getOptions();
        $id = (string) $element->getId();

        if ((!array_key_exists('id', $attributes) || $attributes['id'] == $id) && '' !== $id) {
            $attributes['id'] = 'fieldset-' . $id;
        }

        $factory = $element->getFactory();
        $engine = $factory->getTemplate();
        $helpers = $engine->getHelpers();

        return $engine->renderDefault(fn(RenderCollection $render) => $render->at('form.html.fieldset')([
            '{attributes}' => function() use ($attributes, $element) {
                $attributes['name'] ??= $element->getFullyQualifiedName();
                $attributes['id']   ??= $attributes['name'];
                return $attributes;
            },
            '{content}' => function($render) use ($content, $helpers) {
                $html = $this->getLegend() !== '' 
                    ? $render->at('form.html.legend')([
                        '{content}' => $helpers->escaper()->escapeHtml(trim($this->getLegend()))
                    ]) 
                    :  '';
                $html .= $content;
                return $html;
            }
        ]));
    }
}
