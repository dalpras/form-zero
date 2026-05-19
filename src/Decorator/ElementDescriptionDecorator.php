<?php declare(strict_types=1);

namespace DalPraS\FormZero\Decorator;

use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\SmartTemplate\Collection\RenderCollection;

class ElementDescriptionDecorator extends AbstractDecorator
{
    public function render(string $content = ''): string
    {
        /** @var \DalPraS\FormZero\Element $element */
        $element = $this->getElement();
        $factory = $element->getFactory();
        $engine = $factory->getTemplate();

        return $engine->renderDefault(function(RenderCollection $render, string $name) use ($content, $element) {
            $description = $render->at('form.components.description')($element, $name);
            if ($this->getOption('collapsible') === true) {
                return $content . $render->at('form.html.description-collapse')([
                    '{id}' => $element->getId(),
                    '{content}' => $description
                ]);
            }
            return $content . $description;
        });
    }
}