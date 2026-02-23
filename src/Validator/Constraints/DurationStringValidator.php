<?php declare(strict_types=1);

namespace DalPraS\FormZero\Validator\Constraints;

use DateInterval;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class DurationStringValidator extends ConstraintValidator
{
    /**
     * @param mixed $value
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof DurationString) {
            throw new UnexpectedTypeException($constraint, DurationString::class);
        }

        // Let NotBlank/NotNull handle "required"; here empty is considered "no constraint".
        if ($value === null || $value === '') {
            return;
        }

        if (!\is_scalar($value) && !(\is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedValueException($value, 'string');
        }

        $value = (string) $value;

        try {
            // If parsing fails, it throws a \Throwable (Number of PHP versions treat it as Exception).
            $interval = DateInterval::createFromDateString($value);
        } catch (\Throwable $th) {
            $interval = false;
        }

        if ($interval === false) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
