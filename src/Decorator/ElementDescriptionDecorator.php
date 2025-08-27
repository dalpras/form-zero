<?php declare(strict_types=1);

namespace DalPraS\FormZero\Decorator;

use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\SmartTemplate\Collection\RenderCollection;
use DalPraS\SmartTemplate\TemplateEngine;

class ElementDescriptionDecorator extends AbstractDecorator
{
    public function render(string $content = ''): string
    {
        /** @var \DalPraS\FormZero\Element $element */
        $element = $this->getElement();
        $factory = $element->getFactory();
        
        return $factory->getTemplate()->render($factory->getTemplateFile(), function(RenderCollection $render, TemplateEngine $template, string $name) use ($content, $element) {

            $description = $render['form']['components']['description']($template, $element, $name);
            if ($this->getOption('collapsible') === true) {
                return $content . $render['form']['html']['description-collapse']([
                    '{id}' => $element->getId(),
                    '{description}' => $description
                ]);
            }
            return $content . $description;
        });
    }
}