<?php declare(strict_types=1);

namespace DalPraS\FormZero\Element;

use DalPraS\FormZero\Element;
use DalPraS\FormZero\Element\Intefaces\MultiChoicesInterface;
use DalPraS\FormZero\Element\Traits\MultiChoicesTrait;

final class SelectElement extends Element implements MultiChoicesInterface
{
    use MultiChoicesTrait;
}
