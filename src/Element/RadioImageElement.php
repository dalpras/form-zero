<?php declare(strict_types=1);

namespace DalPraS\FormZero\Element;

use DalPraS\FormZero\Element\MultiElement;
use Symfony\Component\Validator\Constraints as Assert;

final class RadioImageElement extends MultiElement
{
    public function isValid($value, $context = null): bool
    {
        $multiChoices = $this->getMultiChoices();
        // internal: [label => value], we want the values
        $choices = array_values($multiChoices);

        if (!empty($choices)) {
            $this->addConstraint(new Assert\Choice([
                'choices'  => $choices,
            ]));
        }

        return parent::isValid($value, $context);
    }
}
