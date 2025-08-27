<?php declare(strict_types=1);

namespace DalPraS\FormZero\Element;

use DalPraS\FormZero\Element;
use Laminas\Validator\Identical;

class SubmitElement extends Element
{
    /**
     * Is the value provided the same?
     */
    public function isValid($value, $context = null): bool
    {
        $this->getValidatorChain()->attachByName(Identical::class, ['token' => $value], true);
        return parent::isValid($value, $context);
    }
}
