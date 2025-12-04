<?php declare(strict_types=1);

namespace DalPraS\FormZero\Element;

use DalPraS\FormZero\Element;
use DalPraS\FormZero\Element\Traits\MultiChoicesTrait;
use DalPraS\FormZero\Element\Intefaces\MultiChoicesInterface;

class MultiElement extends Element implements MultiChoicesInterface
{
    use MultiChoicesTrait;

    public function __construct()
    {
        // This says: "this element may have multiple values"
        $this->setIsArray(true);
        $this->setAttrib('multiple', true);
    }

    public function isValid($value, $context = null): bool
    {
        $this->appendChoicesToConstraints();
        return parent::isValid($value, $context);
    }
}
