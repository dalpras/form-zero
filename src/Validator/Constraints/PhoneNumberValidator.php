<?php

declare(strict_types=1);

namespace DalPraS\FormZero\Validator\Constraints;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class PhoneNumberValidator extends ConstraintValidator
{
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

        if (str_starts_with($raw, '00')) {
            $raw = '+' . substr($raw, 2);
        }

        $phoneUtil = PhoneNumberUtil::getInstance();
        $hasInternationalPrefix = str_starts_with($raw, '+');

        if (!$hasInternationalPrefix && !$constraint->country) {
            $this->context->buildViolation($constraint->invalidMessage)
                ->setParameter('{{ value }}', $this->formatValue($raw))
                ->addViolation();
            return;
        }

        try {
            $parseRegion = $hasInternationalPrefix ? null : $constraint->country;
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

        if (!$phoneUtil->isValidNumber($numberProto)) {
            $this->context->buildViolation($constraint->invalidMessage)
                ->setParameter('{{ value }}', $this->formatValue($raw))
                ->addViolation();
            return;
        }

        if (!$hasInternationalPrefix && $constraint->country) {
            if (!$phoneUtil->isValidNumberForRegion($numberProto, strtoupper($constraint->country))) {
                $this->context->buildViolation($constraint->invalidMessage)
                    ->setParameter('{{ value }}', $this->formatValue($raw))
                    ->addViolation();
            }
        }
    }
}