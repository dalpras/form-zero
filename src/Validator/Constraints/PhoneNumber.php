<?php declare(strict_types=1);

namespace DalPraS\FormZero\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that a value is a valid phone number using libphonenumber.
 *
 * Usage:
 *   new PhoneNumber()                    // no default region
 *   new PhoneNumber(['country' => 'IT']) // use "IT" as default region
 */
class PhoneNumber extends Constraint
{
    /**
     * Default region (e.g. "IT", "US", "FR") passed to libphonenumber::parse().
     * Can be null if you want strict E.164 with leading "+".
     */
    public ?string $country = null;

    // Message templates (you can translate these via validators.*.xlf)
    public string $countryMismatchMessage      = 'The phone number prefix does not match the selected country.';
    public string $invalidCountryCodeMessage   = 'The country code did not belong to a supported country.';
    public string $invalidMessage              = 'Invalid phone number.';
    public string $notANumberMessage           = 'The string is not a number.';
    public string $tooShortAfterIddMessage     = 'The number has less digits than any valid phone number.';
    public string $tooShortNsnMessage          = 'The prefix has less digits than any valid phone number.';

    public function __construct(?array $options = null, ?array $groups = null, $payload = null)
    {
        parent::__construct($options ?? [], $groups, $payload);
    }

    public function validatedBy(): string
    {
        return static::class.'Validator';
    }
}
