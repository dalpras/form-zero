<?php declare(strict_types=1);

namespace DalPraS\FormZero\Decorator;

use Closure;
use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\SmartTemplate\Collection\RenderCollection;
use Throwable;

/**
 * Wrap the content using a specific callback function.
 *
 * @deprecated use instead CallbackDecorator
 * 
 * Example:
 * (new TemplateRenderDecorator(['callback' => fn(string $content, RenderCollection $render) => $content . 'postfix']))->render()
 *
 */
class TemplateRenderDecorator extends AbstractDecorator
{
    /**
     * Render content through a callback.
     * @deprecated use instead CallbackDecorator
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
            $template = $factory->getTemplate();

            try {
                return $template->render($factory->getTemplateFile(), fn(RenderCollection $render, string $name) => $callback($content, $render, $element, $name));
            } catch (Throwable $th) {
                return $th->getMessage() . $th->getTraceAsString();
            }
        }
        return $content;
    }
}

