<?php declare(strict_types=1);

namespace DalPraS\FormZero\Element;

use DalPraS\FormZero\Element\MultiElement;
use Symfony\Component\Validator\Constraints as Assert;

final class RadioImageElement extends MultiElement
{
    public function isValid($value, $context = null): bool
    {
        $multiChoices = $this->getMultiChoices();
        $choices      = array_keys($multiChoices);

        if (!empty($choices)) {
            $this->addConstraint(new Assert\Choice([
                'choices'  => $choices,
                // 'multiple' => $this->isArray(),
            ]));
        }

        return parent::isValid($value, $context);
    }
}
