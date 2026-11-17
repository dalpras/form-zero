<?php declare(strict_types=1);

namespace DalPraS\FormZero\Decorator;

use DalPraS\FormZero\ZeroForm;

/**
 * Render all form elements registered with current form.
 */
class ElementsDecorator extends AbstractDecorator
{
    /**
     * Render elements and subforms without mutating form structure/state.
     */
    public function render(string $content = ''): string
    {
        $form = $this->getElement();
        $items = [];

        foreach ($form as $item) {
            $items[] = $item->render();
        }

        $separator = $this->getSeparator();
        return $content . $separator . implode($separator, $items);
    }
}
