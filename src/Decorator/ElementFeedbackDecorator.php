<?php declare(strict_types=1);

namespace DalPraS\FormZero\Decorator;

use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\SmartTemplate\Collection\RenderCollection;

class ElementFeedbackDecorator extends AbstractDecorator
{
    public function render(string $content = ''): string
    {
        /** @var \DalPraS\FormZero\Element $element */
        $element = $this->getElement();
        $factory = $element->getFactory();
        return $factory->getTemplate()->render($factory->getTemplateFile(), fn(RenderCollection $render, string $name)
            => $content . $render['form']['components']['feedback']($element, $name)
        );
    }
}