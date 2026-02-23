<?php declare(strict_types=1);

namespace DalPraS\FormZero\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that a string can be parsed into a \DateInterval
 * using DateInterval::createFromDateString().
 *
 * Examples of valid values:
 *   "1 day"
 *   "2 days"
 *   "3 weeks"
 *   "1 month"
 */
class DurationString extends Constraint
{
    /**
     * The error message shown when the value cannot be parsed.
     */
    public string $message = 'Durata non valida.';

    public function validatedBy(): string
    {
        return static::class . 'Validator';
    }
}
