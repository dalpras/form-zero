<?php declare(strict_types=1);

namespace DalPraS\FormZero\Decorator;

use DalPraS\FormZero\ZeroForm;
use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\FormZero\Element\SymfileElement;
use DalPraS\FormZero\ElementInterface;
use DalPraS\FormZero\SubZeroForm;
use InvalidArgumentException;

/**
 * Render all form elements registered with current form
 */
class ElementsDecorator extends AbstractDecorator
{
    /**
     * Merges given two belongsTo (array notation) strings
     */
    private function mergeBelongsTo(string $baseBelongsTo, string $belongsTo): string
    {
        $endOfArrayName = strpos($belongsTo, '[');

        if ($endOfArrayName === false) {
            return $baseBelongsTo . '[' . $belongsTo . ']';
        }

        $arrayName = substr($belongsTo, 0, $endOfArrayName);

        return $baseBelongsTo . '[' . $arrayName . ']' . substr($belongsTo, $endOfArrayName);
    }

    /**
     * Funzione che ricorsivamente renderizza gli elementi attraverso i decoratori.
     * Nel caso di subforms con decoratore FormElement, richiamerà se stessa fino alla fine dei sub-rendering.
     */
    public function render(string $content = ''): string
    {
        // Element, Form or Subform
        $form = $this->getElement();

        // Form and SubZeroForm
        if ($form instanceof ZeroForm) {
            $belongsTo = $form->getElementsBelongTo();
        } else {
            $belongsTo = '';
        }
        $items = [];

        // scorro elementi e subforms
        /** @var \DalPraS\FormZero\Element|\DalPraS\FormZero\SubZeroForm $item */
        foreach ($form as $item) {

            switch (true) {
                // se l'item è un Element imposto belongsTo
                case $item instanceof ElementInterface:
                    $item->setBelongsTo($belongsTo);
                    break;

                // se è una subform e vi è un valore belongsTo impostato a livello di form
                case $item instanceof SubZeroForm && $belongsTo !== '' && $item->isArray():
                    $itemElementsBelongsTo = $item->getElementsBelongTo();
                    $name = $this->mergeBelongsTo($belongsTo, $itemElementsBelongsTo);
                    $item->setElementsBelongTo($name);
                    break;

                case $item instanceof SubZeroForm && $belongsTo !== '':
                    $item->setElementsBelongTo($belongsTo);
                    break;

                case $item instanceof SubZeroForm:
                    break;

                case $item instanceof ZeroForm:
                    throw new InvalidArgumentException('Cannot add Forms to Form use SubZeroForms');
            }

            // renderizzo l'elemento
            $items[] = $item->render();

            switch (true) {
                case ($form instanceof ZeroForm) === false:
                    break;
                case $item instanceof SymfileElement:
                case ($item instanceof ZeroForm) && ($item->getAttrib('enctype') == 'multipart/form-data'):
                    $form->setAttrib('enctype', 'multipart/form-data');
            }
        }
        $separator = $this->getSeparator();
        $result = $content . $separator . implode($separator, $items);
        return $result;
    }
}
