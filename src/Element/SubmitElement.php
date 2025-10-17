<?php declare(strict_types=1);

namespace DalPraS\FormZero\Element;

use DalPraS\FormZero\Element;
use Laminas\Validator\Identical;

class SubmitElement extends Element
{
    public array $options = [
        'text'   => ''
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
     */
    public function isValid($value, $context = null): bool
    {
        $this->getValidatorChain()->attachByName(Identical::class, ['token' => $value], true);
        return parent::isValid($value, $context);
    }
}
