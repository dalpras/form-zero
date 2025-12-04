<?php declare(strict_types=1);

namespace DalPraS\FormZero\Element;

use DalPraS\FormZero\Element;
use DalPraS\FormZero\Element\Intefaces\MultiChoicesInterface;
use DalPraS\FormZero\Element\Traits\MultiChoicesTrait;

final class SelectElement extends Element implements MultiChoicesInterface
{
    use MultiChoicesTrait;

    public function isValid($value, $context = null): bool
    {
        $this->appendChoicesToConstraints(); // same logic as MultiElement
        return parent::isValid($value, $context);
    }

}
