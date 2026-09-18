<?php declare(strict_types=1);

namespace DalPraS\FormZero\Decorator;

use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\FormZero\ZeroForm;
use DalPraS\SmartTemplate\Collection\RenderCollection;

/**
 * Render a Form.
 *
 * Accepts following options:
 * - separator: Separator to use between elements
 * - helper: which view helper to use when rendering form. Should accept three
 *   arguments, string content, a name, and an array of attributes.
 * - mandatory: true forces the mandatory-fields legend, false suppresses it,
 *   null/omitted renders it automatically when a required labeled element exists.
 *
 * Any other options passed will be used as HTML attributes of the form tag.
 */
class FormDecorator extends AbstractDecorator
{
    /**
     * Render a form
     *
     * Replaces $content entirely from currently set element.
     */
    public function render(string $content = ''): string
    {
        $element = $this->getElement();
        $factory = $element->getFactory();
        $engine = $factory->template();

        return $engine->renderDefault(function(RenderCollection $render) use ($element, $content) {
            $attributes       = $element->getAttribs();
            $attributes['id'] = $element->getId();

            if ($element->requiresMultipartEncoding()) {
                $attributes['enctype'] = 'multipart/form-data';
            }

            $attributes['name'] ??= $element->getFullyQualifiedName();
            $attributes['id']   ??= $attributes['name'];

            $method = $attributes['method'] ?? null;
            unset($attributes['method']);

            $action = $attributes['action'] ?? null;
            unset($attributes['action']);

            return $render->at('tag.form')([
                '{action}' => $action,
                '{method}' => $method,
                '{attributes}' => $attributes,
                '{content}'   => function(RenderCollection $render) use ($content, $element) {
                    $html = $content;
                    $mandatory = $this->getOption('mandatory');
                    $showMandatory = $mandatory === null
                        ? $this->hasRequiredLabeledElement($element)
                        : (bool) $mandatory;

                    if ($showMandatory) {
                        $html .= $render->at('form.components.mandatory')($render);
                    }
                    return $html;
                }
            ]);
        });
    }

    private function hasRequiredLabeledElement(ZeroForm $form): bool
    {
        foreach ($form->getElements() as $element) {
            if ($element->isRequired() && trim($element->getLabel()) !== '') {
                return true;
            }
        }

        foreach ($form->getSubForms() as $subForm) {
            if ($this->hasRequiredLabeledElement($subForm)) {
                return true;
            }
        }

        return false;
    }
}
