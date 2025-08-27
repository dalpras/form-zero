<?php declare(strict_types=1);

namespace DalPraS\FormZero\Decorator;

use DalPraS\FormZero\Decorator\AbstractDecorator;

class ElementContentDecorator extends AbstractDecorator
{
    public function render(string $content = ''): string
    {
        /** @var \DalPraS\FormZero\Element $element */
        $element = $this->getElement();

        $content = (new ElementBaseDecorator())->setElement($element)->render($content);
        $content = (new ElementDescriptionDecorator(['collapsible' => $this->getOption('collapsible')]))->setElement($element)->render($content);
        $content = (new ElementFeedbackDecorator())->setElement($element)->render($content);
        return $content;
    }
}