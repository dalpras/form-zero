<?php declare(strict_types=1);

namespace DalPraS\FormZero\Decorator;

use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\SmartTemplate\Collection\RenderCollection;

class ElementRowColContentDecorator extends AbstractDecorator
{
    /**
     * Render element in Bootstrap row style
     */
    public function render(string $content = ''): string
    {
        $element = $this->getElement();
        $factory = $element->getFactory();
        return $factory->getTemplate()->render($factory->getTemplateFile(), function(RenderCollection $render) use ($content) {
            return $render['form']['html']['col-content']([
                '{class}'   => $this->getOption('class') ?? '',
                '{content}' => $content
            ]);
        });
    }
}