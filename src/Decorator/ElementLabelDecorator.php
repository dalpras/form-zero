<?php declare(strict_types=1);

namespace DalPraS\FormZero\Decorator;

use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\SmartTemplate\Collection\RenderCollection;
use DalPraS\SmartTemplate\TemplateEngine;

class ElementLabelDecorator extends AbstractDecorator
{
    public function render(string $content = ''): string
    {
        $element = $this->getElement();
        $factory = $element->getFactory();
        return $factory->getTemplate()->render($factory->getTemplateFile(), function(RenderCollection $render, TemplateEngine $template) use ($content, $element) {
            if ( $element->getLabel() !== '' ) {
                /** @var \DalPraS\SmartTemplate\TemplateEngine $template */
                $helpers =  $template->getHelpers();
                return $render['form']['html']['label']([
                        '{class}'    => $this->getOption('class') ?? 'form-label',
                        '{for}'      => $this->getOption('for') ?? $element->getId(),
                        '{required}' => $element->isRequired() ? 'required' : '',
                        '{text}'     => $element->isTranslatorDisabled() ? $element->getLabel() : $helpers->translator()->translate($element->getLabel())
                    ]) . $content;
            }
            return $content;
        });
    }
}