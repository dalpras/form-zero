<?php declare(strict_types=1);

namespace DalPraS\FormZero\Decorator;

use DalPraS\FormZero\Decorator\AbstractDecorator;

class GroupRowColLabelDecorator extends AbstractDecorator
{
    /**
     * Render element in Bootstrap row style
     */
    public function render(string $content = ''): string
    {
        /** @var \DalPraS\FormZero\Element $element */
        $element = $this->getElement();
        $content = (new ElementLabelDecorator(['class' => 'col-form-label ' . ($this->getOption('class') ?? '') ]))->setElement($element)->render($content);
        return $content;
    }
}