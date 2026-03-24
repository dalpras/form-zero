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
        $template = $factory->getTemplate();
        
        return $template->render($factory->getTemplateFile(), function(RenderCollection $render) use ($content) {
            $options = $this->getOption('options');

            return $render['form']['html']['accordion-item']([
                '{id}'  => $this->getOption('id'),

                '{attributes}' => $this->getOption('draggable') ? [
                    'data-dragger'   => "item",
                    'data-object-id' => $options['{objectId}'] ?? ''
                ] : [],

                '{content}' => $render['form']['html']['accordion-item-content']([
                    '{class}'      => ($options['{class}'] ?? '') . ($this->getOption('collapsed') ? '' : ' show'),
                    '{attributes}' => array_replace($options['attributes'] ?? [], $this->getOption('draggable') ? ['data-dragger' => 'draggable'] : []),
                    '{pid}'        => $this->getOption('pid'),
                    '{id}'         => $this->getOption('id'),
                    '{content}'    => $content,
                    '{trigger}'    => $render['form']['html']['item-trigger']([
                        '{class}' => $this->getOption('collapsed') ? 'collapsed' : '',
                        '{id}'    => $this->getOption('id'),
                        '{text}'  => $options['{text}'] ?? ''
                    ]),
                    '{mover}'   => $this->getOption('draggable')
                        ? $render['form']['html']['item-mover']()
                        : '',
                    '{buttons}' => ($options['{buttons}'] ?? '') !== ''
                        ? $render['form']['html']['item-buttons'](['{content}' => $options['{buttons}']])
                        : '',
                ])
            ]);
        });
    }
}