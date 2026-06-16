<?php

declare(strict_types=1);

namespace DalPraS\FormZero\Filter;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class PhoneNumberFilter implements FilterInterface
{
    protected string $message = '';
    protected PhoneNumberUtil $phoneUtil;

    /**
     * @param array{country?: string} $options
     */
    public function __construct(private ?array $options = null)
    {
        $this->phoneUtil = PhoneNumberUtil::getInstance();
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function filter(mixed $value): mixed
    {
        $this->message = '';

        if (!is_scalar($value) || $value === '') {
            return $value;
        }

        $raw = trim((string) $value);

        // Supporta 00 come prefisso internazionale
        if (str_starts_with($raw, '00')) {
            $raw = '+' . substr($raw, 2);
        }

        $defaultCountry = $this->options['country'] ?? null;

        // Se non ho prefisso e non ho country → non posso interpretare
        if (!str_starts_with($raw, '+') && !$defaultCountry) {
            $this->message = 'No country specified for phone number validation.';
            return (string) $value;
        }

        try {
            // 1) Parse: internazionale se +..., altrimenti nazionale con defaultCountry
            $parseRegion = str_starts_with($raw, '+') ? null : $defaultCountry;
            $numberProto = $this->phoneUtil->parse($raw, $parseRegion);

            // 2) Validazione
            if (str_starts_with($raw, '+')) {
                // numero internazionale: validità generale
                if (!$this->phoneUtil->isValidNumber($numberProto)) {
                    $this->message = 'The phone number is not valid.';
                    return (string) $value;
                }

                // se il form impone un country, verifica che corrisponda
                if ($defaultCountry) {
                    $region = $this->phoneUtil->getRegionCodeForNumber($numberProto); // es. "IT"
                    if (!$region || strtoupper($region) !== strtoupper($defaultCountry)) {
                        $this->message = 'The phone number does not belong to the specified country.';
                        return (string) $value;
                    }
                }
            } else {
                // numero nazionale: validità per quella regione
                if (!$this->phoneUtil->isValidNumberForRegion($numberProto, $defaultCountry)) {
                    $this->message = 'The phone number is not valid for the specified country.';
                    return (string) $value;
                }
            }

            // 3) Normalizzazione storage: E.164 sempre
            return $this->phoneUtil->format($numberProto, PhoneNumberFormat::E164);
        } catch (NumberParseException $e) {
            $this->message = match ($e->getErrorType()) {
                NumberParseException::INVALID_COUNTRY_CODE =>
                'The country code supplied did not belong to the specified country.',
                NumberParseException::NOT_A_NUMBER =>
                'The string passed is not a valid number.',
                NumberParseException::TOO_SHORT_AFTER_IDD,
                NumberParseException::TOO_SHORT_NSN =>
                'The phone number is too short for the specified country.',
                default =>
                'The phone number is not valid. Please enter a valid phone number.',
            };

            return (string) $value;
        }
    }
}
