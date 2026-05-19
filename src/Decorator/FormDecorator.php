<?php declare(strict_types=1);

namespace DalPraS\FormZero\Decorator;

use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\SmartTemplate\Collection\RenderCollection;

/**
 * Render a Form.
 *
 * Accepts following options:
 * - separator: Separator to use between elements
 * - helper: which view helper to use when rendering form. Should accept three
 *   arguments, string content, a name, and an array of attributes.
 *
 * Any other options passed will be used as HTML attributes of the form tag.
 */
class FormDecorator extends AbstractDecorator
{
    /**
     * Render a form
     *
     * Replaces $content entirely from currently set element.
     */
    public function render(string $content = ''): string
    {
        $element = $this->getElement();
        $factory = $element->getFactory();
        $engine = $factory->getTemplate();

        return $engine->renderDefault(function(RenderCollection $render) use ($element, $content) {
            $attributes       = $element->getAttribs();
            $attributes['id'] = $element->getId();

            $attributes['name'] ??= $element->getFullyQualifiedName();
            $attributes['id']   ??= $attributes['name'];

            return $render->at('form.html.form')([
                '{attributes}' => $attributes,
                '{content}'   => function(RenderCollection $render) use ($content) {
                    $html = $content;
                    $mandatory = (bool) ($this->getOption('mandatory') ?? false);
                    if ($mandatory === true) {
                        $html .= $render->at('form.components.mandatory')($render);
                    }
                    return $html;
                }
            ]);
        });
    }
}
