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
        $engine = $factory->template();

        return $engine->renderDefault(function(RenderCollection $render) use ($content, $element) {
            $class = $this->getOption('class') ?? null;

            $attributes = $this->getOption('attributes') ?? [];
            $fn = ($attributes instanceof Closure) 
                ? $attributes 
                : fn() => ['class' => $class];


            return $render->at('tag.div')([
                '{attributes}' => $fn($element),
                '{content}'    => $content
            ]);
        });
    }
}