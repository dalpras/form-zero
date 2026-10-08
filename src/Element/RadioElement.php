<?php declare(strict_types=1);

namespace DalPraS\FormZero\Element;

use DalPraS\FormZero\Element;
use DalPraS\FormZero\Element\Intefaces\ChoicesAlignmentInterface;
use DalPraS\FormZero\Element\Traits\MultiChoicesTrait;
use DalPraS\FormZero\Element\Intefaces\MultiChoicesInterface;
use DalPraS\FormZero\Element\Traits\ChoicesAlignmentTrait;

final class RadioElement extends Element implements MultiChoicesInterface, ChoicesAlignmentInterface
{
    use MultiChoicesTrait;
    use ChoicesAlignmentTrait;

}
