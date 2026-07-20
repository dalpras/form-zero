<?php declare(strict_types=1);

namespace DalPraS\FormZero\Decorator;

use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\SmartTemplate\Collection\RenderCollection;

class ElementLabelDecorator extends AbstractDecorator
{
    public function render(string $content = ''): string
    {
        $element = $this->getElement();
        $factory = $element->getFactory();
        $engine = $factory->template();
        $helpers = $engine->getHelpers();
        
        return $engine->renderDefault(function(RenderCollection $render) use ($content, $element, $helpers) {
            if ( $element->getLabel() !== '' ) {
                return $render->at('tag.label')([
                    '{attributes}' => [
                        'class'    => $this->getOption('class') ?? 'form-label' . ($element->isRequired() ? 'required' : ''),
                        'for'      => $this->getOption('for') ?? $element->getId(),
                    ],
                    '{content}'     => $element->isTranslatorDisabled() 
                        ? $element->getLabel() 
                        : $helpers->translator()->trans($element->getLabel())
                ]) .  $content;
            }
            return $content;
        });
    }
}