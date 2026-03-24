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
        $template = $factory->getTemplate();
        $helpers = $template->getHelpers();
        
        return $template->render($factory->getTemplateFile(), function(RenderCollection $render) use ($content, $element, $helpers) {
            if ( $element->getLabel() !== '' ) {
                return $render['form']['html']['label']([
                        '{class}'    => $this->getOption('class') ?? 'form-label',
                        '{for}'      => $this->getOption('for') ?? $element->getId(),
                        '{required}' => $element->isRequired() ? 'required' : '',
                        '{text}'     => $element->isTranslatorDisabled() ? $element->getLabel() : $helpers->translator()->trans($element->getLabel())
                    ]) . 
                    $content;
            }
            return $content;
        });
    }
}