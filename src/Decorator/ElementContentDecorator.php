<?php declare(strict_types=1);

namespace DalPraS\FormZero\Decorator;

use Closure;
use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\FormZero\Element;
use DalPraS\SmartTemplate\Collection\RenderCollection;

class ElementContentDecorator extends AbstractDecorator
{
    public function render(string $content = ''): string
    {
        /** @var \DalPraS\FormZero\Element $element */
        $element = $this->getElement();

        $prefix = $this->getOption('prefix') ?? null;
        if ($prefix !== null) {
            $callback = new CallbackDecorator(['callback' => function(string $content, RenderCollection $render, Element $element, $namespace) use ($prefix) {
                $resolvedPrefix = ($prefix instanceof Closure) ? $prefix($render) : $prefix;
                if ($resolvedPrefix === '' || $resolvedPrefix === null) {
                    return (new ElementBaseDecorator())->setElement($element)->render($content);
                }

                return $render->at('tag.div')([
                        '{attributes}|join' => ['class' => 'input-group form-zero-control-group'],
                        '{content}'    => $render->at('tag.span')([
                            '{attributes}|join' => ['class' => 'input-group-text bg-transparent border-end-0'],
                            '{content}' => $resolvedPrefix
                        ]) . 
                       (new ElementBaseDecorator(['attributes' => ['class' => 'border-start-0']]))->setElement($element)->render($content) 
                    ]);
            }]);
            $content = $callback->setElement($element)->render($content);
        } else {
            $content = (new ElementBaseDecorator())->setElement($element)->render($content);
        }

        $content = (new ElementDescriptionDecorator(['collapsible' => $this->getOption('collapsible')]))
            ->setElement($element)
            ->render($content)
        ;

        $content = (new ElementFeedbackDecorator())
            ->setElement($element)
            ->render($content)
        ;
        return $content;
    }
}