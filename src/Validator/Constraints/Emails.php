<?php declare(strict_types=1);

namespace DalPraS\FormZero\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validates a string that contains one or more email addresses
 * separated by a configurable separator (default: ";").
 *
 * Examples with default separator (";"):
 *   "a@foo.it; b@bar.com"
 *   "one@example.com"
 *
 * Usage:
 *   new Emails() // default separator ";"
 *   new Emails(['separator' => '; '])
 */
class Emails extends Constraint
{
    /**
     * Error message; {{ value }} is replaced with the invalid email.
     */
    public string $message = 'One or more email addresses are invalid: "{{ value }}".';

    /**
     * Separator used to split the string.
     * Example: ";", "; ", ",", "," (trim is applied on each part anyway).
     */
    public string $separator = '; ';

    /**
     * Let Symfony map options array keys (e.g. ['separator' => '; '])
     * directly to properties.
     */
    public function __construct(?array $options = null, ?array $groups = null, $payload = null)
    {
        parent::__construct($options ?? [], $groups, $payload);
    }

    public function validatedBy(): string
    {
        return static::class . 'Validator';
    }
}
