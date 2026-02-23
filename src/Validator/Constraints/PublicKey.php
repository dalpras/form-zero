<?php declare(strict_types=1);

namespace DalPraS\FormZero\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that the input is a valid PEM public key.
 */
class PublicKey extends Constraint
{
    /**
     * Default error message (translatable via validators.*.xlf)
     */
    public string $message = 'The provided public key is not valid.';

    public function validatedBy(): string
    {
        return static::class . 'Validator';
    }
}
