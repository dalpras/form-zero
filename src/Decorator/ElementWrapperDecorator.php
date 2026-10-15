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
            $class = $this->getOption('class');
            $configuredAttributes = $this->getOption('attributes') ?? [];
            $attributes = $configuredAttributes instanceof Closure
                ? $configuredAttributes($element)
                : $configuredAttributes;

            // Keep the existing class-only contract, but do not discard
            // explicitly configured wrapper attributes.
            if ($class !== null && !array_key_exists('class', $attributes)) {
                $attributes['class'] = $class;
            }

            return $render->at('tag.div')([
                '{attributes}|join' => $attributes,
                '{content}'    => $content
            ]);
        });
    }
}