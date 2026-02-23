<?php declare(strict_types=1);

namespace DalPraS\FormZero\Validator\Constraints;

use DalPraS\FormZero\Validator\Constraints\NotLink;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class NotLinkValidator extends ConstraintValidator
{
    /**
     * @param mixed $value
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof NotLink) {
            throw new UnexpectedTypeException($constraint, NotLink::class);
        }

        if ($value === null || $value === '') {
            return; // empty is OK (use NotBlank to enforce required)
        }

        if (!is_scalar($value) && !(is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedValueException($value, 'string');
        }

        $value = (string) $value;

        // 1) Block http:// or https://
        if (preg_match('~https?:\/\/~i', $value)) {
            $this->context->buildViolation($constraint->httpMessage)
                ->addViolation();
            return;
        }

        // 2) Block mailto://
        if (preg_match('~mailto:\/\/~i', $value)) {
            $this->context->buildViolation($constraint->mailtoMessage)
                ->addViolation();
            return;
        }

        // 3) Block tel:
        if (preg_match('~tel:~i', $value)) {
            $this->context->buildViolation($constraint->telMessage)
                ->addViolation();
            return;
        }

        // 4) Block any "//"
        if (preg_match('~\/\/~i', $value)) {
            $this->context->buildViolation($constraint->slashMessage)
                ->addViolation();
        }
    }
}
