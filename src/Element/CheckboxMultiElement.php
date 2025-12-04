<?php declare(strict_types=1);

namespace DalPraS\FormZero\Element;

use DalPraS\FormZero\Element\Intefaces\ChoicesAlignmentInterface;
use DalPraS\FormZero\Element\Traits\ChoicesAlignmentTrait;

final class CheckboxMultiElement extends MultiElement implements ChoicesAlignmentInterface
{
    use ChoicesAlignmentTrait;

}
