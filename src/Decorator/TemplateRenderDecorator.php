<?php declare(strict_types=1);

namespace DalPraS\FormZero\Decorator;

use Closure;
use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\SmartTemplate\Collection\RenderCollection;
use DalPraS\SmartTemplate\TemplateEngine;
use Throwable;

/**
 * Wrap the content using a specific callback function.
 *
 * Example:
 * (new TemplateRenderDecorator(['callback' => fn(string $content, RenderCollection $render, TemplateEngine $template, Element|SubZeroForm $element, string $name) => $content . 'postfix']))->render()
 *
 */
class TemplateRenderDecorator extends AbstractDecorator
{
    /**
     * Render content through a callback.
     */
    public function render(string $content = ''): string
    {
        $callback = $this->getOption('callback') ?? '';

        if (is_string($callback)) {
            return $content . $callback;
        }

        if ($callback instanceof Closure) {
            $element = $this->getElement();
            $factory = $element->getFactory();
            try {
                return $factory->getTemplate()->render($factory->getTemplateFile(), fn(RenderCollection $render, TemplateEngine $template, string $name) => $callback($content, $render, $template, $element, $name));
            } catch (Throwable $th) {
                return $th->getMessage() . $th->getTraceAsString();
            }
        }
        return $content;
    }
}

