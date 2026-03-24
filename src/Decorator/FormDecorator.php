<?php declare(strict_types=1);

namespace DalPraS\FormZero\Decorator;

use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\SmartTemplate\Collection\RenderCollection;
use DalPraS\SmartTemplate\TemplateEngine;

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
        $template = $factory->getTemplate();

        return $template->render($factory->getTemplateFile(), function(RenderCollection $render) use ($element, $content) {
            $attribs       = $element->getAttribs();
            $attribs['id'] = $element->getId();

            $attribs['name'] ??= $element->getFullyQualifiedName();
            $attribs['id']   ??= $attribs['name'];

            return $render['form']['html']['form']([
                '{attributes}' => $attribs,
                '{elements}'   => function(RenderCollection $render) use ($content) {
                    $html = $content;
                    $mandatory = (bool) ($this->getOption('mandatory') ?? false);
                    if ($mandatory === true) {
                        $html .= $render['form']['components']['mandatory']($render);
                    }
                    return $html;
                }
            ]);
        });
    }
}
