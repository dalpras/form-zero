<?php declare(strict_types=1);

namespace DalPraS\FormZero\Decorator;

use DalPraS\FormZero\SubZeroForm;
use DalPraS\FormZero\ZeroForm;
use InvalidArgumentException;

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
            if ($item instanceof ZeroForm && !$item instanceof SubZeroForm) {
                throw new InvalidArgumentException('Cannot add Forms to Form use SubZeroForms');
            }

            $items[] = $item->render();
        }

        $separator = $this->getSeparator();
        return $content . $separator . implode($separator, $items);
    }
}
