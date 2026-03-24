<?php declare(strict_types=1);

namespace DalPraS\FormZero\Decorator;

use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\SmartTemplate\Collection\RenderCollection;
use DalPraS\SmartTemplate\TemplateEngine;

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
            $attribs = $element->getAttribs();
            $options = array_merge($attribs, $options);
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

        $attribs = $this->getOptions();
        $id = (string) $element->getId();

        if ((!array_key_exists('id', $attribs) || $attribs['id'] == $id) && '' !== $id) {
            $attribs['id'] = 'fieldset-' . $id;
        }

        $factory = $element->getFactory();
        $template = $factory->getTemplate();
        $helpers = $template->getHelpers();

        return $template->render($factory->getTemplateFile(), fn(RenderCollection $render) => $render['form']['html']['fieldset']([
            '{legend}'  => $this->getLegend() !== '' ? $render['form']['html']['legend']([
                '{text}' => $helpers->escaper()->escapeHtml(trim($this->getLegend()))
            ]) :  '',
            '{attributes}' => function() use ($attribs, $element) {
                $attribs['name'] ??= $element->getFullyQualifiedName();
                $attribs['id']   ??= $attribs['name'];
                return $attribs;
            },
            '{content}' => $content
        ]));
    }
}
