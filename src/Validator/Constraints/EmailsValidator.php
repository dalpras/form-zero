<?php declare(strict_types=1);

namespace DalPraS\FormZero\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class EmailsValidator extends ConstraintValidator
{
    /**
     * @param mixed $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Emails) {
            throw new UnexpectedTypeException($constraint, Emails::class);
        }

        // Allow null/empty: use NotBlank/NotNull if you want required behavior
        if ($value === null || $value === '') {
            return;
        }

        if (!\is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        // Split by the configured separator and trim each part
        $parts = array_map('trim', explode($constraint->separator, $value));

        // Inner Email constraint (you can tweak options: checkMX, checkHost, etc.)
        $emailConstraint = new Email([
            'mode' => Email::VALIDATION_MODE_STRICT, // strict-ish mode
        ]);

        foreach ($parts as $email) {
            if ($email === '') {
                continue;
            }

            $violations = $this->context->getValidator()->validate($email, $emailConstraint);

            if (count($violations) > 0) {
                // We just re-map any underlying email error into a single message
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $email)
                    ->addViolation();
            }
        }
    }
}
