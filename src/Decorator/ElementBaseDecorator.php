<?php declare(strict_types=1);

namespace DalPraS\FormZero\Decorator;

use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\FormZero\Element\CheckboxElement;
use DalPraS\FormZero\Element\CheckboxMultiElement;
use DalPraS\FormZero\Element\DatePickerElement;
use DalPraS\FormZero\Element\EmailElement;
use DalPraS\FormZero\Element\HashElement;
use DalPraS\FormZero\Element\HiddenElement;
use DalPraS\FormZero\Element\PasswordElement;
use DalPraS\FormZero\Element\RadioElement;
use DalPraS\FormZero\Element\SearchElement;
use DalPraS\FormZero\Element\SelectElement;
use DalPraS\FormZero\Element\SelectMultiElement;
use DalPraS\FormZero\Element\SubmitElement;
use DalPraS\FormZero\Element\SymfileElement;
use DalPraS\FormZero\Element\SymfileMultiElement;
use DalPraS\FormZero\Element\TextareaElement;
use DalPraS\FormZero\Element\TextElement;
use DalPraS\SmartTemplate\Collection\RenderCollection;
use Throwable;

class ElementBaseDecorator extends AbstractDecorator
{
    public function render(string $content = ''): string
    {
        /** @var \DalPraS\FormZero\Element $element */
        $element = $this->getElement();
        $factory = $element->getFactory();
        $engine = $factory->getTemplate();
        
        return $engine->renderDefault(function(RenderCollection $render) use ($element, $content) {
            try {
                $html = match (true) {
                    $element instanceof HashElement,
                    $element instanceof HiddenElement,
                    $element instanceof TextElement,
                    $element instanceof EmailElement,
                    $element instanceof PasswordElement,
                    $element instanceof SearchElement,
                    $element instanceof TextareaElement,
                    $element instanceof DatePickerElement,
                    $element instanceof SubmitElement,
                    $element instanceof CheckboxElement,
                    $element instanceof CheckboxMultiElement,
                    $element instanceof SelectElement,
                    $element instanceof SelectMultiElement,
                    $element instanceof RadioElement,
                    $element instanceof SymfileElement,
                    $element instanceof SymfileMultiElement
                        => $render->at('form.element')($element::class)($render, $element),
                    default
                        => 'Invalid element type'
                };
                return $content . $html;
            } catch (Throwable $th) {
                return $content . $th->getMessage() . $th->getTraceAsString();
            }
        });
    }
}