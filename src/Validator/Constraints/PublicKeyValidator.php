<?php declare(strict_types=1);

namespace DalPraS\FormZero\Validator\Constraints;

use DalPraS\FormZero\Validator\Constraints\PublicKey;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class PublicKeyValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof PublicKey) {
            throw new UnexpectedTypeException($constraint, PublicKey::class);
        }

        // Empty is allowed – use NotBlank in your form if required.
        if ($value === null || $value === '') {
            return;
        }

        if (!\is_scalar($value) && !(\is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedValueException($value, 'string');
        }

        $value = (string) $value;

        // Same regex as your Laminas validator
        $isValid = (bool) preg_match(
            '~^(-----BEGIN PUBLIC KEY-----)(.*)(-----END PUBLIC KEY-----)$~ms',
            $value
        );

        if (!$isValid) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
