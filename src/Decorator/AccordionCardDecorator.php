<?php declare(strict_types=1);

namespace DalPraS\FormZero\Decorator;

use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\SmartTemplate\Collection\RenderCollection;

class AccordionCardDecorator extends AbstractDecorator
{
    /**
     * Create an accordion for using in form.
     */
    public function render(string $content = ''): string
    {
        $element = $this->getElement();
        $factory = $element->getFactory();
        $engine = $factory->getTemplate();
        
        return $engine->render($factory->getTemplateFile(), function(RenderCollection $render) use ($content) {
            $options = $this->getOption('options');

            return $render->at('form.html.accordion-item')([
                '{id}'  => $this->getOption('id'),

                '{attributes}' => $this->getOption('draggable') ? [
                    'data-dragger'   => "item",
                    'data-object-id' => $options['{objectId}'] ?? ''
                ] : [],

                '{content}' => $render->at('form.html.accordion-item-content')([
                    '{class}'      => ($options['{class}'] ?? '') . ($this->getOption('collapsed') ? '' : ' show'),
                    '{attributes}' => array_replace($options['attributes'] ?? [], $this->getOption('draggable') ? ['data-dragger' => 'draggable'] : []),
                    '{pid}'        => $this->getOption('pid'),
                    '{id}'         => $this->getOption('id'),
                    '{content}'    => $content,
                    '{trigger}'    => $render->at('form.html.item-trigger')([
                        '{class}' => $this->getOption('collapsed') ? 'collapsed' : '',
                        '{id}'    => $this->getOption('id'),
                        '{text}'  => $options['{text}'] ?? ''
                    ]),
                    '{mover}'   => $this->getOption('draggable')
                        ? $render->at('form.html.item-mover')()
                        : '',
                    '{buttons}' => ($options['{buttons}'] ?? '') !== ''
                        ? $render->at('form.html.item-buttons')(['{content}' => $options['{buttons}']])
                        : '',
                ])
            ]);
        });
    }
}