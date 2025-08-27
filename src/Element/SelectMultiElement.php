<?php declare(strict_types=1);

namespace DalPraS\FormZero\Element;

use DalPraS\FormZero\Element\MultiElement;

class SelectMultiElement extends MultiElement
{
    protected array $attribs = [
        'multiple' => true
    ];
    
    /**
     * Multiselect is an array of values by default
     */
    public function isArray(): bool
    {
        return true;
    }    
}
