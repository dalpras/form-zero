<?php declare(strict_types=1);

namespace DalPraS\FormZero\Element;

class CheckboxMultiElement extends MultiElement
{
    protected array $attribs = [];

    /**
     * Multiselect is an array of values by default
     */
    public function isArray(): bool
    {
        return true;
    }
}
