<?php declare(strict_types=1);

namespace DalPraS\FormZero\Decorator;

use Closure;
use DalPraS\SmartTemplate\Collection\RenderCollection;
use Throwable;

class CallbackDecorator extends AbstractDecorator
{
    /**
     * Render content through a callback.
     *
     * Callback arguments are passed by name to keep the contract explicit and
     * avoid per-render reflection:
     *
     * fn(
     *     string $content,
     *     RenderCollection $render,
     *     ElementInterface|ZeroForm $element,
     *     string $namespace,
     * ): string
     *
     * Callback implementations may omit type declarations, but must keep these
     * parameter names when they are declared because the invocation uses PHP
     * named arguments.
     */
    public function render(string $content = ''): string
    {
        $callback = $this->getOption('callback') ?? '';

        if (is_string($callback)) {
            return $content . $callback;
        }

        if (!$callback instanceof Closure) {
            return $content;
        }

        $element = $this->getElement();
        $engine = $element->getFactory()->template();

        try {
            return $engine->renderDefault(
                fn(RenderCollection $render, string $namespace): string => (string) $callback(
                    content: $content,
                    render: $render,
                    element: $element,
                    namespace: $namespace,
                )
            );
        } catch (Throwable $th) {
            return $th->getMessage() . $th->getTraceAsString();
        }
    }
}
