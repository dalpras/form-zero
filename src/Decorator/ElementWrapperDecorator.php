<?php declare(strict_types=1);

namespace DalPraS\FormZero\Decorator;

use Closure;
use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\SmartTemplate\Collection\RenderCollection;

class ElementWrapperDecorator extends AbstractDecorator
{
    /**
     * Render element in Bootstrap row style
     */
    public function render(string $content = ''): string
    {
        $element = $this->getElement();
        $factory = $element->getFactory();
        $engine = $factory->getTemplate();

        return $engine->renderDefault(function(RenderCollection $render) use ($content, $element) {
            $attributes = $this->getOption('attributes') ?? [];
            $fn = ($attributes instanceof Closure) 
                ? $attributes 
                : fn() => $attributes;
            return $render->at('form.html.content-wrapper')([
                '{class}'      => $this->getOption('class') ?? '',
                '{attributes}' => $fn($element),
                '{content}'    => $content
            ]);
        });
    }
}