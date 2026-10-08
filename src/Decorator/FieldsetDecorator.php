<?php declare(strict_types=1);

namespace DalPraS\FormZero\Decorator;

use DalPraS\SmartTemplate\Collection\RenderCollection;

/**
 * Render a form/subform as a semantic HTML fieldset.
 *
 * Legend rule:
 * - the form/subform legend is configured only through ZeroForm::setLegend();
 * - this decorator is the only component responsible for rendering <legend>;
 * - renderLegend=false may be used only to suppress the legend while keeping
 *   the fieldset wrapper.
 *
 * Any remaining decorator options are used as HTML attributes of <fieldset>.
 */
class FieldsetDecorator extends AbstractDecorator
{
    /**
     * Get fieldset HTML attributes.
     *
     * Decorator-only options must never leak into the generated HTML.
     */
    public function getOptions(): array
    {
        $options = parent::getOptions();
        unset($options['legend'], $options['renderLegend']);

        $attributes = array_merge($this->getElement()->getAttribs(), $options);
        if ($this->getElement() instanceof \DalPraS\FormZero\ZeroForm && $this->getElement()->isNested()) {
            // A standalone form's submission attributes do not belong on
            // its nested <fieldset>. Names/IDs are derived from the new path.
            unset(
                $attributes['action'], $attributes['method'], $attributes['enctype'],
                $attributes['novalidate'], $attributes['accept-charset'],
                $attributes['target'], $attributes['autocomplete'], $attributes['name']
            );
        }

        return $attributes;
    }

    /**
     * Read the legend from the form/subform itself.
     *
     * There is deliberately no independent legend state on this decorator:
     * ZeroForm::setLegend() is the single source of truth.
     */
    private function resolveLegend(): string
    {
        $element = $this->getElement();

        if (!method_exists($element, 'getLegend')) {
            return '';
        }

        return trim((string) $element->getLegend());
    }

    /**
     * Render a fieldset and, when configured, its semantic legend.
     */
    public function render(string $content = ''): string
    {
        /** @var \DalPraS\FormZero\Element|\DalPraS\FormZero\ZeroForm $element */
        $element = $this->getElement();

        $renderLegend = $this->getOption('renderLegend') !== false;
        $legend = $this->resolveLegend();
        $attributes = $this->getOptions();
        $id = (string) $element->getId();

        if ((!array_key_exists('id', $attributes) || $attributes['id'] == $id) && '' !== $id) {
            $attributes['id'] = 'fieldset-' . $id;
        }

        $factory = $element->getFactory();
        $engine = $factory->template();
        $helpers = $engine->getHelpers();

        return $engine->renderDefault(fn(RenderCollection $render) => $render->at('tag.fieldset')([
            '{attributes}' => function() use ($attributes, $element) {
                $attributes['name'] ??= $element->getFullyQualifiedName();
                $attributes['id']   ??= $attributes['name'];
                return $attributes;
            },
            '{content}' => function($render) use ($content, $renderLegend, $legend, $helpers) {
                if (!$renderLegend || $legend === '') {
                    return $content;
                }

                return $render->at('tag.legend')([
                    '{content}' => $helpers->escaper()->escapeHtml($legend),
                ]) . $content;
            },
        ]));
    }
}
