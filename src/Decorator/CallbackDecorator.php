<?php declare(strict_types=1);

namespace DalPraS\FormZero\Decorator;

use Closure;
use DalPraS\FormZero\Decorator\AbstractDecorator;
use Throwable;

class CallbackDecorator extends AbstractDecorator
{
    /**
     * Render element in Bootstrap row style
     */
    public function render(string $content = ''): string
    {
        $callback = $this->getOption('callback') ?? '';

        if (is_string($callback)) {
            return $content . $callback;
        }

        if ($callback instanceof Closure) {
            $element = $this->getElement();
            try {
                return $callback($content, $element);
            } catch (Throwable $th) {
                return $th->getMessage() . $th->getTraceAsString();
            }
        }
        return $content;
    }
}