<?php declare(strict_types=1);

namespace DalPraS\FormZero\Element;

use DalPraS\FormZero\Element;
use Symfony\Component\Validator\Constraints as Assert;

final class SubmitElement extends Element
{
    public array $options = [
        'text' => '',
    ];

    public function setText(string $text): self
    {
        $this->options['text'] = $text;
        return $this;
    }

    public function getText(): string
    {
        return $this->options['text'] ?? '';
    }

    /**
     * Is the value provided the same?
     *
     * Here we mimic the old Identical validator by adding
     * a Symfony IdenticalTo constraint at runtime.
     */
    public function isValid($value, $context = null): bool
    {
        if ($value !== null && $this->getIgnore() === false) {
            $this->addConstraint(new Assert\IdenticalTo([
                'value'   => $value,
                'message' => 'Valore non valido.',
            ]));
        }
        return parent::isValid($value, $context);
    }
}
