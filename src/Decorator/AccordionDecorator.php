<?php declare(strict_types=1);

namespace DalPraS\FormZero\Decorator;

use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\SmartTemplate\Collection\RenderCollection;

class AccordionDecorator extends AbstractDecorator
{
    /**
     * Create an accordion for using in form.
     */
    public function render(string $content = ''): string
    {
        $element = $this->getElement();
        $factory = $element->getFactory();
        return $factory->getTemplate()->render($factory->getTemplateFile(), function(RenderCollection $render) use ($element, $content) {
            return $render['form']['html']['accordion'](
                array_replace_recursive($this->getOption('options'), [
                    '{attributes}' => [
                        'data-row-for' => $element->getId()
                    ],
                    '{pid}' => $this->getOption('pid'),
                    '{content}' => $content
                ])
            );
        });
    }
}