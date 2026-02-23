<?php

declare(strict_types=1);

namespace DalPraS\FormZero\Validator\Constraints;

use DalPraS\FormZero\Validator\Constraints\PhoneNumber;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class PhoneNumberValidator extends ConstraintValidator
{
    /**
     * @param mixed $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof PhoneNumber) {
            throw new UnexpectedTypeException($constraint, PhoneNumber::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        if (!\is_scalar($value) && !(\is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedValueException($value, 'string');
        }

        $raw = trim((string) $value);

        // Supporta 00 come prefisso internazionale
        if (str_starts_with($raw, '00')) {
            $raw = '+' . substr($raw, 2);
        }

        $phoneUtil = PhoneNumberUtil::getInstance();

        // Se non ho prefisso e non ho country nel constraint → non interpretabile
        if (!str_starts_with($raw, '+') && !$constraint->country) {
            $this->context->buildViolation($constraint->invalidMessage)
                ->setParameter('{{ value }}', $this->formatValue($raw))
                ->addViolation();
            return;
        }

        try {
            $parseRegion = str_starts_with($raw, '+') ? null : $constraint->country;
            $numberProto = $phoneUtil->parse($raw, $parseRegion);
        } catch (NumberParseException $e) {
            $message = match ($e->getErrorType()) {
                NumberParseException::INVALID_COUNTRY_CODE => $constraint->invalidCountryCodeMessage,
                NumberParseException::NOT_A_NUMBER         => $constraint->notANumberMessage,
                NumberParseException::TOO_SHORT_AFTER_IDD  => $constraint->tooShortAfterIddMessage,
                NumberParseException::TOO_SHORT_NSN        => $constraint->tooShortNsnMessage,
                default                                    => $constraint->invalidMessage,
            };

            $this->context->buildViolation($message)
                ->setParameter('{{ value }}', $this->formatValue($raw))
                ->addViolation();

            return;
        }

        // Validità generale
        if (!$phoneUtil->isValidNumber($numberProto)) {
            $this->context->buildViolation($constraint->invalidMessage)
                ->setParameter('{{ value }}', $this->formatValue($raw))
                ->addViolation();
            return;
        }

        // Se il constraint impone una nazione, verifica la coerenza:
        if ($constraint->country) {
            if (str_starts_with($raw, '+')) {
                // input internazionale: verifica che appartenga al country richiesto
                $region = $phoneUtil->getRegionCodeForNumber($numberProto);
                if (!$region || strtoupper($region) !== strtoupper($constraint->country)) {
                    $this->context->buildViolation($constraint->countryMismatchMessage)
                        ->setParameter('{{ value }}', $this->formatValue($raw))
                        ->addViolation();
                    return;
                }
            } else {
                // input nazionale: verifica per regione (più stretto)
                if (!$phoneUtil->isValidNumberForRegion($numberProto, $constraint->country)) {
                    $this->context->buildViolation($constraint->invalidMessage)
                        ->setParameter('{{ value }}', $this->formatValue($raw))
                        ->addViolation();
                    return;
                }
            }
        }
    }
}
